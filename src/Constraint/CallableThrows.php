<?php
/**
 * PHPUnitThrowableAssertions - Throwable-related PHPUnit assertions.
 *
 * @copyright Copyright (c) 2021, Daniel Rudolf (<https://www.daniel-rudolf.de>)
 *
 * This file is copyrighted by the contributors recorded in the version control
 * history of the file, available from the following original location:
 *
 * <https://github.com/PhrozenByte/phpunit-throwable-asserts/blob/master/src/Constraint/CallableThrows.php>
 *
 * @license http://opensource.org/licenses/MIT The MIT License
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

declare(strict_types=1);

namespace PhrozenByte\PHPUnitThrowableAsserts\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Exception as PHPUnitException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Util\Exporter;
use PhrozenByte\PHPUnitThrowableAsserts\InvalidArrayAssertTestArgumentException;
use Throwable;

/**
 * Constraint that asserts that a Callable throws a specific Throwable.
 *
 * This constraint calls the given Callable and catches any Throwable matching
 * the given base class. Any other Throwable isn't caught. It then asserts that
 * the Throwable's class, message and code match the expected, or throws a
 * ExpectationFailedException otherwise.
 *
 * The class name of the expected Throwable, a optional constraint to match the
 * Throwable's message, the optional code to assert, whether an exact match of
 * the Throwable's class is required, and the Throwable base class name are
 * passed in the constructor. The Callable is the value to evaluate.
 */
class CallableThrows extends AbstractCallableThrows
{
    /** @var string */
    protected $baseClassName;

    /**
     * CallableThrows constructor.
     *
     * @param string                 $className     assert that a Throwable of the given class is thrown
     * @param Constraint|string|null $message       assert that the Throwable matches the given message constraint
     * @param int|string|null        $code          assert that the Throwable matches the given code
     * @param bool                   $exactMatch    whether an exact match of the Throwable class is required
     * @param string                 $baseClassName catch all Throwables of the given class
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $className = Throwable::class,
        $message = null,
        $code = null,
        bool $exactMatch = false,
        string $baseClassName = Throwable::class
    ) {
        $baseClassName = ltrim($baseClassName, '\\');
        if (!is_a($baseClassName, Throwable::class, true)) {
            throw InvalidArrayAssertTestArgumentException::create(5, sprintf('instance of %s', Throwable::class));
        }

        if (!is_a($className, $baseClassName, true)) {
            throw InvalidArrayAssertTestArgumentException::create(1, sprintf('instance of %s (argument #5)', $baseClassName));
        }

        parent::__construct($className, $message, $code, $exactMatch);

        $this->baseClassName = $baseClassName;
    }

    /**
     * Returns a human-readable string representation of this Constraint.
     *
     * @return string string representation of the Constraint
     */
    public function toString(): string
    {
        return sprintf('throws a %s', $this->className)
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
    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        $throwable = null;
        $comparisonFailure = null;

        if (is_callable($other)) {
            try {
                $other();
            } catch (Throwable $throwable) {
                if (!($throwable instanceof $this->baseClassName)) {
                    throw $throwable;
                }

                if ($throwable instanceof $this->className) {
                    $success = true;

                    if ($this->exactMatch && (get_class($throwable) !== $this->className)) {
                        $success = false;
                    }

                    if ($this->messageConstraint !== null) {
                        try {
                            $this->messageConstraint->evaluate($throwable->getMessage());
                        } catch (ExpectationFailedException $messageException) {
                            $comparisonFailure = $messageException->getComparisonFailure();
                            $success = false;
                        }
                    }

                    if ($this->code !== null) {
                        if ($throwable->getCode() !== $this->code) {
                            $success = false;
                        }
                    }

                    if ($success) {
                        return $returnResult ? true : null;
                    }
                }
            }
        }

        if (!$returnResult) {
            $this->fail($other, $description, $comparisonFailure, $throwable);
        }

        return false;
    }
}
