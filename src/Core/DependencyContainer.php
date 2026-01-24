<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Core\Interfaces\ContainerInterface;
use RuntimeException;

/**
 * Class DependencyContainer
 *
 * Simple dependency injection container
 */
class DependencyContainer implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $services = [];

    /**
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * @inheritDoc
     */
    public function register(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) {
            if (!isset($this->factories[$id])) {
                throw new RuntimeException("Service not found: $id");
            }
            $this->services[$id] = $this->factories[$id]($this);
        }
        return $this->services[$id];
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->services[$id]);
    }
}