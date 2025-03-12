<?php
declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts;


use InvalidArgumentException;
use Throwable;

class InvalidArrayAssertTestArgumentException extends InvalidArgumentException
{
#Expected :'Argument #2 of PhrozenByte\PHPUnitArrayAsserts\Assert::assertAssociativeArray() must be an array or ArrayAccess'
#Actual   :'Argument 2 of PhrozenByte\PHPUnitThrowableAsserts\ArrayAssert is invalid: array or ArrayAccess'

    public static function create(int $arg, string $message): self {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1];
        
        $class = $caller['class'] ?? '';
        $function = $caller['function'] ?? '';
        $method = $class ? "$class::$function" : $function;

        return new self(
            sprintf('Argument #%d of %s() must be an %s', $arg, $method, $message)
        );
    }
}
