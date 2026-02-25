<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * Interface Container
 *
 * Describes the interface of a dependency injection container.
 * Extends PSR-11 ContainerInterface with service registration capability.
 */
interface Container extends ContainerInterface
{
    /**
     * Register a service factory
     *
     * @param string $id Service identifier
     * @param callable $factory Factory callable that creates the service
     * @return void
     */
    public function register(string $id, callable $factory): void;
}