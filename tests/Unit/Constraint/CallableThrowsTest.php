<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/tests/Unit/Constraint/CallableThrowsTest.php>
 *
 * @license http://opensource.org/licenses/MIT The MIT License
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts\Tests\Unit\Constraint;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PhrozenByte\PHPUnitThrowableAsserts\Constraint\CallableThrows;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\TestCase;

/**
 * PHPUnit unit test for the CallableThrows constraint.
 *
 * @see CallableThrows
 *
 * @covers \PhrozenByte\PHPUnitThrowableAsserts\Constraint\CallableThrows
 * @covers \PhrozenByte\PHPUnitThrowableAsserts\Constraint\AbstractCallableThrows
 */
class CallableThrowsTest extends TestCase
{
    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     * @throws \Throwable
     */
    #[DataProvider('dataProviderInvalidParameters')]
    public function testInvalidParameters(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $constraintArguments = [ $className, $message, $code, $exactMatch, $baseClassName ];
        $this->assertCallableThrows(static function () use ($constraintArguments) {
            new CallableThrows(...$constraintArguments);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     * @return array[]
     */
    public static function dataProviderInvalidParameters(): array
    {
        return self::getTestDataSets('testInvalidParameters');
    }

    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param string $expectedDescription
     * @throws \Throwable
     */
    #[DataProvider('dataProviderSelfDescribing')]
    public function testSelfDescribing(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        string $expectedDescription
    ): void {
        $constraint = null;
        $constraintArguments = [ $className, $message, $code, $exactMatch, $baseClassName ];

        $this->assertCallableThrowsNot(static function () use (&$constraint, $constraintArguments) {
            $constraint = new CallableThrows(...$constraintArguments);
        });

        $this->assertSame($expectedDescription, $constraint->toString());
    }

    /**
     * @return array
     */
    public static function dataProviderSelfDescribing(): array
    {
        return self::getTestDataSets('testSelfDescribing');
    }

    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param callable $other
     * @throws \Throwable
     */
    #[DataProvider('dataProviderEvaluate')]
    public function testEvaluate(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        $other
    ): void {
        $constraint = new CallableThrows($className, $message, $code, $exactMatch, $baseClassName);

        $this->assertCallableThrowsNot(static function () use ($constraint, $other) {
            $constraint->evaluate($other);
        });
    }

    /**
     * @return array
     */
    public static function dataProviderEvaluate(): array
    {
        return self::getTestDataSets('testEvaluate');
    }

    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param callable $other
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     * @throws \Throwable
     */
    #[DataProvider('dataProviderEvaluateFail')]
    public function testEvaluateFail(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        $other,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $constraint = new CallableThrows($className, $message, $code, $exactMatch, $baseClassName);

        $this->assertCallableThrows(static function () use ($constraint, $other) {
            $constraint->evaluate($other);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     * @return array
     */
    public static function dataProviderEvaluateFail(): array
    {
        return self::getTestDataSets('testEvaluateFail');
    }

    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param callable $other
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     * @throws \Throwable
     */
    #[DataProvider('dataProviderEvaluateRethrow')]
    public function testEvaluateRethrow(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        $other,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $constraint = new CallableThrows($className, $message, $code, $exactMatch, $baseClassName);

        $this->assertCallableThrows(static function () use ($constraint, $other) {
            $constraint->evaluate($other);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     * @return array
     */
    public static function dataProviderEvaluateRethrow(): array
    {
        return self::getTestDataSets('testEvaluateRethrow');
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateNoThrow(): void
    {
        $expectedException = ExpectationFailedException::class;
        $expectedExceptionMessage = 'Failed asserting that ' . __CLASS__ . '::{closure}() throws a Throwable.';

        $constraint = new CallableThrows();
        $other = function () {};

        $this->assertCallableThrows(static function () use ($constraint, $other) {
            $constraint->evaluate($other);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateNoCallable(): void
    {
        $expectedException = ExpectationFailedException::class;
        $expectedExceptionMessage = "Failed asserting that 'no callable' is a callable that throws a Throwable.";

        $constraint = new CallableThrows();
        $other = 'no callable';

        $this->assertCallableThrows(static function () use ($constraint, $other) {
            $constraint->evaluate($other);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateReturnsNull(): void
    {
        $constraint = new CallableThrows();
        $other = static function () {
            throw new Exception();
        };

        $returnValue = null;
        $this->assertCallableThrowsNot(static function () use ($constraint, $other, &$returnValue) {
            $returnValue = $constraint->evaluate($other);
        });

        $this->assertNull($returnValue);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateReturnsTrue(): void
    {
        $constraint = new CallableThrows();
        $other = static function () {
            throw new Exception();
        };

        $returnValue = null;
        $this->assertCallableThrowsNot(static function () use ($constraint, $other, &$returnValue) {
            $returnValue = $constraint->evaluate($other, '', true);
        });

        $this->assertTrue($returnValue);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateReturnsFalse(): void
    {
        $constraint = new CallableThrows();
        $other = static function () {};

        $returnValue = null;
        $this->assertCallableThrowsNot(static function () use ($constraint, $other, &$returnValue) {
            $returnValue = $constraint->evaluate($other, '', true);
        });

        $this->assertFalse($returnValue);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateReturnsFalseInvalid(): void
    {
        $constraint = new CallableThrows();
        $other = 'no callable';

        $returnValue = null;
        $this->assertCallableThrowsNot(static function () use ($constraint, $other, &$returnValue) {
            $returnValue = $constraint->evaluate($other, '', true);
        });

        $this->assertFalse($returnValue);
    }

    /**
     * @throws \Throwable
     */
    public function testEvaluateCustomMessage(): void
    {
        $expectedException = ExpectationFailedException::class;
        $expectedExceptionMessage = "This is a unit test.\n"
            . "Failed asserting that " . __CLASS__ . "::{closure}() throws a Throwable.";

        $constraint = new CallableThrows();
        $other = static function () {};
        $message = 'This is a unit test.';

        $this->assertCallableThrows(static function () use ($constraint, $other, $message) {
            $constraint->evaluate($other, $message);
        }, $expectedException, $expectedExceptionMessage);
    }

    /**
     *
     * @param string $className
     * @param Constraint|string|null $message
     * @param int|string|null $code
     * @param bool $exactMatch
     * @param string $baseClassName
     * @param int $expectedCount
     * @throws \Throwable
     */
    #[DataProvider('dataProviderCountable')]
    public function testCountable(
        string $className,
        $message,
        $code,
        bool $exactMatch,
        string $baseClassName,
        int $expectedCount
    ): void {
        $constraint = null;
        $constraintArguments = [ $className, $message, $code, $exactMatch, $baseClassName ];

        $this->assertCallableThrowsNot(static function () use (&$constraint, $constraintArguments) {
            $constraint = new CallableThrows(...$constraintArguments);
        });

        $this->assertSame($expectedCount, $constraint->count());
    }

    /**
     * @return array[]
     */
    public static function dataProviderCountable(): array
    {
        return self::getTestDataSets('testCountable');
    }
}
