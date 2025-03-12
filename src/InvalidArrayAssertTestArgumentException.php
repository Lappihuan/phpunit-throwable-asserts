<?php
declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts;


use InvalidArgumentException;
use Throwable;

class InvalidArrayAssertTestArgumentException extends InvalidArgumentException
{
    public static function create(int $arg, string $message): self {
        return new self(
            sprintf('Argument %d of %s is invalid: %s', $arg, ArrayAssert::class, $message)
        );
    }
}
