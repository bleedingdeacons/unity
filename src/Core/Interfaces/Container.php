<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed
     * @throws NotFoundExceptionInterface No entry was found for the identifier.
     */
    public function get(string $id): mixed;
}