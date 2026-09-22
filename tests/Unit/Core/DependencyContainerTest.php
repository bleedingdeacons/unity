<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Psr\Container\NotFoundExceptionInterface;
use Unity\Core\DependencyContainer;

/*
 * Tests for DependencyContainer
 */

beforeEach(function () {
    $this->container = new DependencyContainer();
});

it('can register and retrieve a service', function () {
    $service = new \stdClass();
    $service->name = 'TestService';

    $this->container->register('test.service', function () use ($service) {
        return $service;
    });

    $retrieved = $this->container->get('test.service');

    expect($retrieved)->toBe($service)
        ->and($retrieved->name)->toEqual('TestService');
});

it('returns the same instance on subsequent calls', function () {
    $callCount = 0;

    $this->container->register('singleton.service', function () use (&$callCount) {
        $callCount++;
        return new \stdClass();
    });

    $first = $this->container->get('singleton.service');
    $second = $this->container->get('singleton.service');

    expect($second)->toBe($first)
        ->and($callCount)->toEqual(1, 'Factory should only be called once');
});

// Caught by hand rather than with ->throws() or toThrow(): both only treat
// their argument as an exception type when it names a class (a typed
// toThrow() closure included), and NotFoundExceptionInterface is an interface,
// so Pest reads it as a message instead. The PSR-11 contract, not the concrete
// class, is the thing being asserted.
it('throws an exception for an unregistered service', function () {
    $thrown = null;

    try {
        $this->container->get('nonexistent.service');
    } catch (\Throwable $e) {
        $thrown = $e;
    }

    expect($thrown)->toBeInstanceOf(NotFoundExceptionInterface::class)
        ->and($thrown?->getMessage())->toContain('Dependency not registered: nonexistent.service');
});

it('can check if a service is registered', function () {
    expect($this->container->has('test.service'))->toBeFalse();

    $this->container->register('test.service', function () {
        return new \stdClass();
    });

    expect($this->container->has('test.service'))->toBeTrue();
});

it('passes the container to the factory', function () {
    $this->container->register('dependency', function () {
        return 'I am a dependency';
    });

    $this->container->register('service.with.dependency', function (DependencyContainer $c) {
        $dependency = $c->get('dependency');
        $service = new \stdClass();
        $service->dependency = $dependency;
        return $service;
    });

    $service = $this->container->get('service.with.dependency');

    expect($service->dependency)->toEqual('I am a dependency');
});

it('can register a service with an interface as key', function () {
    $this->container->register(TestInterface::class, function () {
        return new TestImplementation();
    });

    $service = $this->container->get(TestInterface::class);

    expect($service)->toBeInstanceOf(TestInterface::class)
        ->toBeInstanceOf(TestImplementation::class);
});

it('can override a registered service', function () {
    $this->container->register('service', function () {
        return 'original';
    });

    $this->container->register('service', function () {
        return 'overridden';
    });

    // Note: The factory is overridden, but if the service was already instantiated,
    // it would still return the original. In this case, we haven't called get() yet.
    expect($this->container->get('service'))->toEqual('overridden');
});

it('has() returns true for an instantiated service', function () {
    $this->container->register('service', function () {
        return new \stdClass();
    });

    // Before getting, has() should still return true (factory is registered)
    expect($this->container->has('service'))->toBeTrue();

    // Get the service to instantiate it
    $this->container->get('service');

    // After getting, has() should still return true
    expect($this->container->has('service'))->toBeTrue();
});

/**
 * Test interface for DI testing
 */
interface TestInterface
{
    public function doSomething(): string;
}

/**
 * Test implementation for DI testing
 */
class TestImplementation implements TestInterface
{
    public function doSomething(): string
    {
        return 'done';
    }
}
