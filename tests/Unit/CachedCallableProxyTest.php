<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/tests/Unit/CachedCallableProxyTest.php>
 *
 * @license http://opensource.org/licenses/MIT The MIT License
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts\Tests\Unit;

use Closure;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PhrozenByte\PHPUnitThrowableAsserts\CachedCallableProxy;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\TestCase;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\Utils\InvocableClass;

/**
 * PHPUnit unit test for the CachedCallableProxy helper class.
 *
 * @see CachedCallableProxy
 *
 * @covers \PhrozenByte\PHPUnitThrowableAsserts\CachedCallableProxy
 */
class CachedCallableProxyTest extends TestCase
{
    /**
     *
     * @param callable $callable
     * @param array    $arguments
     * @param string   $expectedDescription
     */
    #[DataProvider('dataProviderSelfDescribing')]
    public function testSelfDescribing(
        callable $callable,
        array $arguments,
        string $expectedDescription
    ): void {
        $callableProxy = new CachedCallableProxy($callable, ...$arguments);
        $this->assertSame($this->normalizeClosureName($expectedDescription), $this->normalizeClosureName($callableProxy->toString()));
    }

    /**
     * @psalm-suppress NullArgument
     *
     * @return array
     */
    public static function dataProviderSelfDescribing(): array
    {
        return [
            [ 'count', [], 'count()' ],
            [ [ Closure::class, 'bind' ], [], 'Closure::bind()' ],
            [ [ new Exception(), 'getMessage' ], [], 'Exception::getMessage()' ],
            [ [ InvocableClass::class, 'otherStaticMethod' ], [], InvocableClass::class . '::otherStaticMethod()' ],
            [ [ new InvocableClass(), 'otherMethod' ], [], InvocableClass::class . '::otherMethod()' ],
            [ function () {}, [], __CLASS__ . '::{closure}()' ],
            [ static function () {}, [], __CLASS__ . '::{closure}()' ],
            [ Closure::bind(function () {}, null, null ), [], '{closure}()' ],
            [ Closure::bind(function () {}, new Exception(), null), [], 'Exception::{closure}()' ],
            [ Closure::bind(function () {}, new Exception(), InvocableClass::class), [], 'Exception::{closure}()' ],
            [ Closure::bind(function () {}, null, InvocableClass::class), [], InvocableClass::class . '::{closure}()' ],
            [ new CachedCallableProxy('count'), [], 'count()' ],
            [ new InvocableClass(), [], InvocableClass::class . '::__invoke()' ],
        ];
    }

    public function testInvocation(): void
    {
        $arguments = [1, 2, 3];

        /** @var InvocableClass&MockObject $invokable */
        $invokable = $this->getMockBuilder(InvocableClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $invokable->expects($this->once())
            ->method('__invoke')
            ->with(...$arguments)
            ->willReturn($arguments);

        $callableProxy = new CachedCallableProxy($invokable, ...$arguments);
        $this->assertSame($arguments, $callableProxy());
    }

    public function testGetReturnValue(): void
    {
        $arguments = [1, 2, 3];

        /** @var InvocableClass&MockObject $invokable */
        $invokable = $this->getMockBuilder(InvocableClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $invokable->expects($this->once())
            ->method('__invoke')
            ->with(...$arguments)
            ->willReturn($arguments);

        $callableProxy = new CachedCallableProxy($invokable, ...$arguments);

        $returnValue = null;
        $this->assertCallableThrowsNot(function () use ($callableProxy, &$returnValue) {
            $returnValue = $callableProxy();
        });

        $this->assertSame($arguments, $returnValue);
        $this->assertSame($arguments, $callableProxy->getReturnValue());
        $this->assertNull($callableProxy->getThrowable());
    }

    public function testGetThrowable(): void
    {
        $arguments = [1, 2, 3];

        // Use the real invokable that throws, not a mock.
        $invokable = new InvocableClass(Exception::class);

        $callableProxy = new CachedCallableProxy($invokable, ...$arguments);

        $returnValue = null;
        $this->assertCallableThrows(function () use ($callableProxy, &$returnValue) {
            $returnValue = $callableProxy();
        }, Exception::class);

        $this->assertNull($returnValue);
        $this->assertNull($callableProxy->getReturnValue());
        $this->assertInstanceOf(Exception::class, $callableProxy->getThrowable());
    }
}
