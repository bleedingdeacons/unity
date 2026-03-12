<?php

declare(strict_types=1);

namespace Unity;

use RuntimeException;
use Unity\Core\DependencyContainer;
use Unity\Core\Interfaces\Container;
use Unity\Core\Interfaces\Cache;
use Unity\Core\UnityServiceProvider;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\Groups\Interfaces\GroupFactory;
use Unity\Groups\Interfaces\GroupRepository;
use Unity\Groups\Interfaces\GroupViewFactory;
use Unity\Meetings\Interfaces\MeetingFactory;
use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Members\Interfaces\MemberChangeTracker;
use Unity\Members\Interfaces\MemberFactory;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Positions\Interfaces\PositionChangeTracker;
use Unity\Positions\Interfaces\PositionFactory;
use Unity\Positions\Interfaces\PositionRepository;
use Unity\Positions\Interfaces\PositionViewFactory;

/**
 * Main Plugin Class
 */
class Plugin
{
    private static ?Container $container = null;

    /**
     * Initialize the plugin (legacy method for backwards compatibility)
     */
    public static function init(): void
    {
        self::initContainer();
        self::initServices();
    }

    /**
     * Initialize the dependency container and register default services
     */
    public static function initContainer(): void
    {
        if (self::$container === null) {
            self::$container = new DependencyContainer();
            $provider = new UnityServiceProvider();
            $provider->register(self::$container);

            register_deactivation_hook(
                dirname(__DIR__, 2) . '/Unity.php',
                [self::class, 'deactivate']
            );
        }
    }

    /**
     * Initialize and resolve all core services
     */
    public static function initServices(): void
    {
        if (self::$container === null) {
            throw new RuntimeException('Container not initialized. Call initContainer() first.');
        }

        // Initialize tracker services (to ensure scrutiny)
        self::$container->get(GroupChangeTracker::class);
        self::$container->get(MemberChangeTracker::class);
        self::$container->get(PositionChangeTracker::class);
    }

    /**
     * Get the dependency container
     *
     * @return Container
     * @throws RuntimeException If plugin is not initialized
     */
    public static function getContainer(): Container
    {
        if (self::$container === null) {
            throw new RuntimeException('Plugin not initialized');
        }
        return self::$container;
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate(): void
    {
        // Cleanup code here if needed
    }
}