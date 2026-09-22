<?php

declare(strict_types=1);

namespace Unity\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use RuntimeException;
use stdClass;
use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\Container;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingChangeTracker;
use Unity\Members\Interfaces\MemberChangeTracker;
use Unity\Plugin;
use Unity\Positions\Interfaces\PositionChangeTracker;

/*
 * Tests for {@see Plugin} — the instance-based bootstrap with a
 * backward-compatible static façade over a global default instance.
 *
 * Covers instance creation (default and injected container), eager tracker
 * resolution and its idempotency, and the static entry points (init /
 * initContainer / initServices / getContainer / getInstance / setInstance /
 * deactivate) including their not-initialised guards. The base TestCase resets
 * the global instance between tests, so no static state leaks.
 */

it('builds a container with Unity\'s own bindings when created without one', function () {
    $plugin = Plugin::create();

    $container = $plugin->getContainerInstance();
    expect($container)->toBeInstanceOf(Container::class)
        // UnityServiceProvider registered Cache (and Configuration) into it.
        ->and($container->has(Cache::class))->toBeTrue();
});

it('wraps a supplied container verbatim', function () {
    $container = Mockery::mock(Container::class);
    $plugin = Plugin::create($container);

    expect($plugin->getContainerInstance())->toBe($container);
});

it('resolves the four trackers exactly once in initializeServices', function () {
    Functions\when('wp_log')->justReturn(null); // logDebug no-ops

    $container = Mockery::mock(Container::class);
    foreach (
        [
        GroupChangeTracker::class,
        MemberChangeTracker::class,
        PositionChangeTracker::class,
        IntergroupMeetingChangeTracker::class,
        ] as $tracker
    ) {
        $container->shouldReceive('get')->with($tracker)->once()->andReturn(new stdClass());
    }

    $plugin = Plugin::create($container);
    $plugin->initializeServices();
    // Second call must short-circuit — the ->once() expectations above
    // would fail if the trackers were resolved again.
    $plugin->initializeServices();
});

it('creates the default and registers the deactivation hook in initContainer', function () {
    Functions\expect('register_deactivation_hook')->once();

    Plugin::initContainer();
    expect(Plugin::getContainer())->toBeInstanceOf(Container::class);

    // Idempotent: a second call must not create another instance or
    // re-register the hook (the ->once() above enforces the latter).
    Plugin::initContainer();
});

it('resolves services on the seeded default instance in init', function () {
    Functions\when('wp_log')->justReturn(null);

    $container = Mockery::mock(Container::class);
    $container->shouldReceive('get')->times(4)->andReturn(new stdClass());

    // Seed the default instance so init()'s initContainer() is a no-op and
    // initServices() resolves against this (tracker-bound) container.
    Plugin::setInstance(Plugin::create($container));

    Plugin::init();
});

it('throws from initServices when the container was not initialised', function () {
    Plugin::setInstance(null);

    Plugin::initServices();
})->throws(RuntimeException::class);

it('throws from getInstance before boot', function () {
    Plugin::setInstance(null);

    Plugin::getInstance();
})->throws(RuntimeException::class);

it('throws from getContainer before boot', function () {
    Plugin::setInstance(null);

    Plugin::getContainer();
})->throws(RuntimeException::class);

it('manages the global instance through set, get and deactivate', function () {
    $plugin = Plugin::create(Mockery::mock(Container::class));

    Plugin::setInstance($plugin);
    expect(Plugin::getInstance())->toBe($plugin);

    Plugin::deactivate();

    expect(fn () => Plugin::getInstance())->toThrow(RuntimeException::class, 'not initialized');
});
