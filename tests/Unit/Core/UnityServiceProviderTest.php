<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use Unity\Core\UnityServiceProvider;
use PHPUnit\Framework\TestCase;
use Unity\Core\DependencyContainer;
use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\Configuration;
use Unity\Core\UnityConfiguration;
use Unity\Core\WordPressCache;

/**
 * Tests for {@see UnityServiceProvider} — the provider that registers Unity's
 * own two bindings (Cache and Configuration) into the container. Resolving
 * them through a real container also runs the registered factory closures.
 */
class UnityServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_cache_and_configuration_bindings(): void
    {
        $container = new DependencyContainer();
        (new UnityServiceProvider())->register($container);

        $this->assertTrue($container->has(Cache::class));
        $this->assertTrue($container->has(Configuration::class));
    }

    #[Test]
    public function the_cache_binding_resolves_to_a_wordpress_cache(): void
    {
        $container = new DependencyContainer();
        (new UnityServiceProvider())->register($container);

        $this->assertInstanceOf(WordPressCache::class, $container->get(Cache::class));
    }

    #[Test]
    public function the_configuration_binding_resolves_to_a_unity_configuration(): void
    {
        $container = new DependencyContainer();
        (new UnityServiceProvider())->register($container);

        $this->assertInstanceOf(UnityConfiguration::class, $container->get(Configuration::class));
    }
}
