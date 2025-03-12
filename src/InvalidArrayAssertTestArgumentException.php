<?php
declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts;


use InvalidArgumentException;
use Throwable;

class InvalidArrayAssertTestArgumentException extends InvalidArgumentException
{
    public static function create(int $code, string $message): self {
        return new self($message, $code);
    }
}
