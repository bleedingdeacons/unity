<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Unity\Core\DependencyContainer;
use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\Configuration;
use Unity\Core\UnityConfiguration;
use Unity\Core\UnityServiceProvider;
use Unity\Core\WordPressCache;

/*
 * Tests for {@see UnityServiceProvider} — the provider that registers Unity's
 * own two bindings (Cache and Configuration) into the container. Resolving
 * them through a real container also runs the registered factory closures.
 */

beforeEach(function () {
    $this->container = new DependencyContainer();
    (new UnityServiceProvider())->register($this->container);
});

it('registers the cache and configuration bindings', function () {
    expect($this->container->has(Cache::class))->toBeTrue()
        ->and($this->container->has(Configuration::class))->toBeTrue();
});

it('resolves the cache binding to a WordPress cache', function () {
    expect($this->container->get(Cache::class))->toBeInstanceOf(WordPressCache::class);
});

it('resolves the configuration binding to a Unity configuration', function () {
    expect($this->container->get(Configuration::class))->toBeInstanceOf(UnityConfiguration::class);
});
