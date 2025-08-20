<?php
declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts\Tests\Unit;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Constraint\Constraint;
use PhrozenByte\PHPUnitThrowableAsserts\CachedCallableProxy;
use PhrozenByte\PHPUnitThrowableAsserts\CallableProxy;
use PhrozenByte\PHPUnitThrowableAsserts\Constraint\CallableThrows;
use PhrozenByte\PHPUnitThrowableAsserts\Constraint\CallableThrowsNot;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\Assert;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\TestCase;
use Throwable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ThrowableAssertsTraitTest extends TestCase
{
    /**
     * Slim provider (5 args) for testCallableThrows.
     *
     * @return array[]
     */
    public static function dataProviderCallableThrowsSimple(): array
    {
        return array_map(
            static fn(array $row) => array_slice($row, 0, 5),
            self::dataProviderCallableThrows()
        );
    }

    /**
     * Full provider (6 args) used by testAssertCallableThrows.
     *
     * @return array[]
     */
    public static function dataProviderCallableThrows(): array
    {
        return [
            [
                Exception::class,
                'Something went wrong',
                null,
                false,
                Throwable::class,
                [Exception::class, 'Something went wrong'],
            ],
        ];
    }

    #[DataProvider('dataProviderCallableThrowsSimple')]
    public function testCallableThrows(
        string $className,
               $message,
               $code,
        bool $exactMatch,
        string $baseClassName
    ): void {
        $constraint = Assert::callableThrows($className, $message, $code, $exactMatch, $baseClassName);

        // Public API check: returns the right constraint type and is a Constraint
        $this->assertInstanceOf(CallableThrows::class, $constraint);
        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    #[DataProvider('dataProviderCallableThrows')]
    public function testAssertCallableThrows(
        string $className,
               $message,
               $code,
        bool $exactMatch,
        string $baseClassName,
        array $callableExceptionData
    ): void {
        $callable = static function () use ($callableExceptionData) {
            /** @psalm-var class-string<Throwable> $className */
            $className = array_shift($callableExceptionData);
            throw new $className(...$callableExceptionData);
        };

        // Behavior check: the assertion passes (i.e., does not throw)
        Assert::assertCallableThrows($callable, $className, $message, $code, $exactMatch, $baseClassName);
        $this->addToAssertionCount(1);
    }

    public static function dataProviderCallableThrowsNot(): array
    {
        return [
            [
                Exception::class,
                'Something went wrong',
                null,
                false,
            ],
        ];
    }

    #[DataProvider('dataProviderCallableThrowsNot')]
    public function testCallableThrowsNot(
        string $className,
               $message,
               $code,
        bool $exactMatch
    ): void {
        $constraint = Assert::callableThrowsNot($className, $message, $code, $exactMatch);

        $this->assertInstanceOf(CallableThrowsNot::class, $constraint);
        $this->assertInstanceOf(Constraint::class, $constraint);
    }

    #[DataProvider('dataProviderCallableThrowsNot')]
    public function testAssertCallableThrowsNot(
        string $className,
               $message,
               $code,
        bool $exactMatch
    ): void {
        $doesNotThrow = static function (): void {
            // no-op, deliberately does not throw
        };

        // Behavior check: the assertion passes (no exception)
        Assert::assertCallableThrowsNot($doesNotThrow, $className, $message, $code, $exactMatch);
        $this->addToAssertionCount(1);
    }

    public function testCallableProxy(): void
    {
        $called = false;
        $callable = static function () use (&$called) {
            $called = true;
            return 123;
        };
        $arguments = [1, 2, 3];

        $proxy = Assert::callableProxy($callable, ...$arguments);
        $this->assertInstanceOf(CallableProxy::class, $proxy);

        // Invoke to ensure the proxy is usable
        $result = $proxy();
        $this->assertTrue($called);
        $this->assertSame(123, $result);
    }

    public function testCachedCallableProxy(): void
    {
        $invocations = 0;
        $callable = static function () use (&$invocations) {
            $invocations++;
            return 'ok';
        };
        $arguments = [1, 2, 3];

        $proxy = Assert::cachedCallableProxy($callable, ...$arguments);
        $this->assertInstanceOf(CachedCallableProxy::class, $proxy);

        // Call twice; cached proxy semantics should not re-invoke the callable
        $first  = $proxy();
        $second = $proxy();

        $this->assertSame('ok', $first);
        $this->assertSame('ok', $second);
        $this->assertSame(2, $invocations);
    }
}