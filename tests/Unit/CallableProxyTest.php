<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/tests/Unit/CallableProxyTest.php>
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
use PhrozenByte\PHPUnitThrowableAsserts\CallableProxy;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\TestCase;
use PhrozenByte\PHPUnitThrowableAsserts\Tests\Utils\InvocableClass;

/**
 * PHPUnit unit test for the CallableProxy helper class.
 *
 * @see CallableProxy
 *
 * @covers \PhrozenByte\PHPUnitThrowableAsserts\CallableProxy
 */
class CallableProxyTest extends TestCase
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
        $callableProxy = new CallableProxy($callable, ...$arguments);
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
            [ new CallableProxy('count'), [], 'count()' ],
            [ new InvocableClass(), [], InvocableClass::class . '::__invoke()' ],
        ];
    }

    public function testInvocation(): void
    {
        $arguments = [1, 2, 3];

        /** @var InvocableClass&\PHPUnit\Framework\MockObject\MockObject $invokable */
        $invokable = $this->getMockBuilder(InvocableClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__invoke'])
            ->getMock();

        $invokable->expects($this->once())
            ->method('__invoke')
            ->with(...$arguments)
            ->willReturn($arguments);

        $callableProxy = new \PhrozenByte\PHPUnitThrowableAsserts\CallableProxy($invokable, ...$arguments);
        $this->assertSame($arguments, $callableProxy());
    }
}
