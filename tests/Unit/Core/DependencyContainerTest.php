<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Unity\Core\DependencyContainer;
use Unity\Core\DependencyNotRegisteredException;

/**
 * Tests for DependencyContainer
 */
class DependencyContainerTest extends TestCase
{
    private DependencyContainer $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new DependencyContainer();
    }

    /**
     * @test
     */
    public function it_can_register_and_retrieve_a_service(): void
    {
        $service = new \stdClass();
        $service->name = 'TestService';

        $this->container->register('test.service', function () use ($service) {
            return $service;
        });

        $retrieved = $this->container->get('test.service');

        $this->assertSame($service, $retrieved);
        $this->assertEquals('TestService', $retrieved->name);
    }

    /**
     * @test
     */
    public function it_returns_same_instance_on_subsequent_calls(): void
    {
        $callCount = 0;

        $this->container->register('singleton.service', function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $first = $this->container->get('singleton.service');
        $second = $this->container->get('singleton.service');

        $this->assertSame($first, $second);
        $this->assertEquals(1, $callCount, 'Factory should only be called once');
    }

    /**
     * @test
     */
    public function it_throws_exception_for_unregistered_service(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Dependency not registered: nonexistent.service');

        $this->container->get('nonexistent.service');
    }

    /**
     * @test
     */
    public function it_can_check_if_service_is_registered(): void
    {
        $this->assertFalse($this->container->has('test.service'));

        $this->container->register('test.service', function () {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('test.service'));
    }

    /**
     * @test
     */
    public function it_passes_container_to_factory(): void
    {
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

        $this->assertEquals('I am a dependency', $service->dependency);
    }

    /**
     * @test
     */
    public function it_can_register_service_with_interface_as_key(): void
    {
        $this->container->register(TestInterface::class, function () {
            return new TestImplementation();
        });

        $service = $this->container->get(TestInterface::class);

        $this->assertInstanceOf(TestInterface::class, $service);
        $this->assertInstanceOf(TestImplementation::class, $service);
    }

    /**
     * @test
     */
    public function it_can_override_registered_service(): void
    {
        $this->container->register('service', function () {
            return 'original';
        });

        $this->container->register('service', function () {
            return 'overridden';
        });

        // Note: The factory is overridden, but if the service was already instantiated,
        // it would still return the original. In this case, we haven't called get() yet.
        $this->assertEquals('overridden', $this->container->get('service'));
    }

    /**
     * @test
     */
    public function has_returns_true_for_instantiated_service(): void
    {
        $this->container->register('service', function () {
            return new \stdClass();
        });

        // Before getting, has() should still return true (factory is registered)
        $this->assertTrue($this->container->has('service'));

        // Get the service to instantiate it
        $this->container->get('service');

        // After getting, has() should still return true
        $this->assertTrue($this->container->has('service'));
    }
}

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
