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
use Unity\Tests\TestCase;

/**
 * Tests for {@see Plugin} — the instance-based bootstrap with a
 * backward-compatible static façade over a global default instance.
 *
 * Covers instance creation (default and injected container), eager tracker
 * resolution and its idempotency, and the static entry points (init /
 * initContainer / initServices / getContainer / getInstance / setInstance /
 * deactivate) including their not-initialised guards. The base TestCase resets
 * the global instance between tests, so no static state leaks.
 */
class PluginTest extends TestCase
{
    /**
     * @test
     */
    public function create_without_a_container_builds_one_with_unitys_own_bindings(): void
    {
        $plugin = Plugin::create();

        $container = $plugin->getContainerInstance();
        $this->assertInstanceOf(Container::class, $container);
        // UnityServiceProvider registered Cache (and Configuration) into it.
        $this->assertTrue($container->has(Cache::class));
    }

    /**
     * @test
     */
    public function create_wraps_a_supplied_container_verbatim(): void
    {
        $container = Mockery::mock(Container::class);
        $plugin = Plugin::create($container);

        $this->assertSame($container, $plugin->getContainerInstance());
    }

    /**
     * @test
     */
    public function initialize_services_resolves_the_four_trackers_exactly_once(): void
    {
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
    }

    /**
     * @test
     */
    public function init_container_creates_the_default_and_registers_the_deactivation_hook(): void
    {
        Functions\expect('register_deactivation_hook')->once();

        Plugin::initContainer();
        $this->assertInstanceOf(Container::class, Plugin::getContainer());

        // Idempotent: a second call must not create another instance or
        // re-register the hook (the ->once() above enforces the latter).
        Plugin::initContainer();
    }

    /**
     * @test
     */
    public function init_resolves_services_on_the_seeded_default_instance(): void
    {
        Functions\when('wp_log')->justReturn(null);

        $container = Mockery::mock(Container::class);
        $container->shouldReceive('get')->times(4)->andReturn(new stdClass());

        // Seed the default instance so init()'s initContainer() is a no-op and
        // initServices() resolves against this (tracker-bound) container.
        Plugin::setInstance(Plugin::create($container));

        Plugin::init();
    }

    /**
     * @test
     */
    public function init_services_throws_when_the_container_was_not_initialised(): void
    {
        Plugin::setInstance(null);

        $this->expectException(RuntimeException::class);
        Plugin::initServices();
    }

    /**
     * @test
     */
    public function get_instance_throws_before_boot(): void
    {
        Plugin::setInstance(null);

        $this->expectException(RuntimeException::class);
        Plugin::getInstance();
    }

    /**
     * @test
     */
    public function get_container_throws_before_boot(): void
    {
        Plugin::setInstance(null);

        $this->expectException(RuntimeException::class);
        Plugin::getContainer();
    }

    /**
     * @test
     */
    public function set_get_and_deactivate_manage_the_global_instance(): void
    {
        $plugin = Plugin::create(Mockery::mock(Container::class));

        Plugin::setInstance($plugin);
        $this->assertSame($plugin, Plugin::getInstance());

        Plugin::deactivate();

        try {
            Plugin::getInstance();
            $this->fail('Expected getInstance() to throw after deactivate().');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('not initialized', $e->getMessage());
        }
    }
}
