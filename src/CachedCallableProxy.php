<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/src/CachedCallableProxy.php>
 *
 * @license http://opensource.org/licenses/MIT The MIT License
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts;

use Override;
use Throwable;

/**
 * A simple proxy class for Callables with return value and Throwable caching.
 *
 * @see CallableProxy
 */
class CachedCallableProxy extends CallableProxy
{
    /** @var mixed|null */
    protected mixed $returnValue;

    /** @var Throwable|null */
    protected ?Throwable $throwable;

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function __invoke()
    {
        $this->returnValue = null;
        $this->throwable = null;

        try {
            $this->returnValue = parent::__invoke();
            return $this->returnValue;
        } catch (Throwable $throwable) {
            $this->throwable = $throwable;
            throw $this->throwable;
        }
    }

    /**
     * Returns the cached return value of the Callable from its last invocation.
     *
     * @return mixed|null the cached return value of the Callable, or NULL
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getReturnValue(): mixed
    {
        return $this->returnValue;
    }

    /**
     * Returns the Throwable that was thrown at the Callable's last invocation.
     *
     * @return Throwable|null the cached Throwable the Callable threw, or NULL
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }
}
