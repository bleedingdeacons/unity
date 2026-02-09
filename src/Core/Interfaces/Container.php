<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

use RuntimeException;

/**
 * Interface Container
 *
 * Describes the interface of a dependency injection container.
 */
interface Container
{
    /**
     * Register a service factory
     *
     * @param string $id Service identifier
     * @param callable $factory Factory callable that creates the service
     * @return void
     */
    public function register(string $id, callable $factory): void;

    /**
     * Get a service by identifier
     *
     * @param string $id Service identifier
     * @return mixed The service instance
     * @throws RuntimeException If service is not found
     */
    public function get(string $id): mixed;

    /**
     * Check if a service is registered
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool;
}