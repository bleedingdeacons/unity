<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\DependencyNotRegisteredException;
use Unity\Core\Interfaces\Container;

/**
 * A resolving stand-in for Unity's dependency container.
 *
 * A service provider does not build anything itself — it hands the container a
 * closure per service. Asserting only that register() was called would leave
 * every one of those closures unexecuted, and they are where the wiring
 * actually lives: which dependencies are optional, what gets passed to each
 * constructor. So this double stores the factories and resolves them on
 * demand, caching the result exactly as {@see \Unity\Core\DependencyContainer}
 * does, and throwing the same exception for an unregistered id.
 *
 * Six near-identical copies of this existed across the suite. It carries the
 * union of what they each offered:
 *
 *   - presets, seeded at construction or via prime(), standing in for the
 *     services Unity itself provides (Configuration, the repositories);
 *   - an optional resolver, called for any id with no preset and no factory,
 *     so a test can answer every unknown dependency with a mock rather than
 *     enumerating them;
 *   - build(), which runs a factory without caching, for asserting on what a
 *     registration produces without disturbing later get() calls;
 *   - registeredIds() and registrationOrder(), for asserting on the wiring.
 */
final class FakeContainer implements Container
{
    /** @var array<string, callable> Registered factories, by id. */
    private array $factories = [];

    /** @var array<string, mixed> Resolved instances and pre-seeded values. */
    private array $instances = [];

    /** @var array<int, string> Ids registered, in order, including repeats. */
    private array $registrations = [];

    /** @var (callable(string): mixed)|null */
    private $resolver;

    /**
     * @param array<string, mixed> $presets Ready-made services, as Unity would supply.
     * @param (callable(string): mixed)|null $resolver Fallback for any other id.
     */
    public function __construct(array $presets = [], ?callable $resolver = null)
    {
        $this->instances = $presets;
        $this->resolver  = $resolver;
    }

    /** Seed a ready-made instance, as Unity would for its own services. */
    public function prime(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function register(string $id, callable $factory): void
    {
        $this->factories[$id]  = $factory;
        $this->registrations[] = $id;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            // Resolve once and remember, matching the real container.
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        if ($this->resolver !== null) {
            return ($this->resolver)($id);
        }

        throw new DependencyNotRegisteredException($id);
    }

    /**
     * Build the service registered under $id by running its factory, without
     * caching the result or consulting the presets.
     */
    public function build(string $id): mixed
    {
        if (!isset($this->factories[$id])) {
            throw new DependencyNotRegisteredException($id);
        }

        return ($this->factories[$id])($this);
    }

    /**
     * Ids that have a registered factory.
     *
     * @return array<int, string>
     */
    public function registeredIds(): array
    {
        return array_keys($this->factories);
    }

    /**
     * Every register() call, in order, including repeats.
     *
     * @return array<int, string>
     */
    public function registrationOrder(): array
    {
        return $this->registrations;
    }
}
