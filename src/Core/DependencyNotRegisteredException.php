<?php

declare(strict_types=1);

namespace Unity\Core;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a requested dependency has not been registered in the container.
 *
 * Implements PSR-11 NotFoundExceptionInterface.
 */
class DependencyNotRegisteredException extends Exception implements NotFoundExceptionInterface
{
    private string $className;

    public function __construct(string $className, int $code = 0, ?Exception $previous = null)
    {
        $message = "Dependency not registered: $className";
        parent::__construct($message, $code, $previous);
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}