<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/src/Constraint/CallableThrowsNot.php>
 *
 * @license http://opensource.org/licenses/MIT The MIT License
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts\Constraint;

use Override;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Exception as PHPUnitException;
use PHPUnit\Framework\ExpectationFailedException;
use PhrozenByte\PHPUnitThrowableAsserts\InvalidArrayAssertTestArgumentException;
use PHPUnit\Util\Exporter;
use Throwable;

/**
 * Constraint that asserts that a Callable doesn't throw a specific Throwable.
 *
 * This constraint calls the given Callable and catches any Throwable matching
 * the given class, message and code. All conditions must match, otherwise the
 * Throwable is re-thrown.
 *
 * This is NOT the same as negating the CallableThrows constraint, which
 * consumes all non-matching Throwables and throws a ExpectationFailedException
 * instead. CallableThrowsNot will rather re-throw any non-matching Throwable.
 * A ExpectationFailedException is only thrown when the Callable throws a
 * Throwable matching all given conditions.
 *
 * The class name of the expected Throwable, a optional constraint to match the
 * Throwable's message, the optional code to assert, and whether an exact match
 * of the Throwable's class is required are passed in the constructor. The
 * Callable is the value to evaluate (`$other`).
 *
 * @psalm-suppress UnusedClass
 */
class CallableThrowsNot extends AbstractCallableThrows
{
    /**
     * CallableThrowsNot constructor.
     *
     * @param string                 $className  assert that no Throwable of the given class is thrown
     * @param Constraint|string|null $message    catch Throwables with a message matching the given constraint only
     * @param int|string|null        $code       catch Throwables with the given code only
     * @param bool                   $exactMatch whether an exact match of the Throwable class is caught only
     *
     */
    public function __construct(
        string $className = Throwable::class,
        $message = null,
        $code = null,
        bool $exactMatch = false
    ) {
        parent::__construct($className, $message, $code, $exactMatch);
    }

    /**
     * Returns a human-readable string representation of this Constraint.
     *
     * @return string string representation of the Constraint
     */
    #[Override]
    public function toString(): string
    {
        return sprintf('does not throw a %s', $this->className)
            . ($this->exactMatch ? ' (exact match)' : '')
            . (($this->code !== null) ? sprintf(' with code %s', Exporter::export($this->code)) : '')
            . (($this->messageConstraint && ($this->code !== null)) ? ' and' : '')
            . ($this->messageConstraint ? ' whose message ' . $this->messageConstraint->toString() : '');
    }

    /**
     * Evaluates whether the given value matches the Constraint.
     *
     * If `$returnResult` is set to `false` (default), an exception is thrown
     * in case of a failure. `null` is returned otherwise.
     *
     * If `$returnResult` is `true`, the result of the evaluation is returned
     * as a boolean instead: `true` in case of success, `false` in case of a
     * failure.
     *
     * @param mixed  $other        the value to evaluate
     * @param string $description  additional information about the test
     * @param bool   $returnResult whether to return the evaluation result
     *
     * @return bool|null evaluation result if `$returnResult` is set `true`
     *
     * @throws ExpectationFailedException
     * @throws PHPUnitException
     */
    #[Override]
    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        if (!is_callable($other)) {
            if (!$returnResult) {
                $this->fail($other, $description);
            }

            return false;
        }

        try {
            $other();
        } catch (Throwable $throwable) {
            if (!($throwable instanceof $this->className)) {
                throw $throwable;
            }

            if ($this->exactMatch && (get_class($throwable) !== $this->className)) {
                throw $throwable;
            }

            if ($this->messageConstraint !== null) {
                try {
                    $this->messageConstraint->evaluate($throwable->getMessage());
                } catch (ExpectationFailedException $messageException) {
                    throw $throwable;
                }
            }

            if ($this->code !== null) {
                if ($throwable->getCode() !== $this->code) {
                    throw $throwable;
                }
            }

            if (!$returnResult) {
                $this->fail($other, $description, null, $throwable);
            }

            return false;
        }

        return $returnResult ? true : null;
    }
}
