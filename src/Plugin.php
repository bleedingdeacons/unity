<?php

declare(strict_types=1);

namespace Unity;

use Unity\Common\Interfaces\CacheInterface;
use Unity\Core\UnityServiceProvider;
use Unity\Core\DependencyContainer;
use Unity\Groups\GroupChangeTracker;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupRepositoryInterface;
use Unity\Groups\Interfaces\GroupViewFactoryInterface;
use Unity\Intergroup\IntergroupManager;
use Unity\Meetings\Interfaces\MeetingFactoryInterface;
use Unity\Meetings\Interfaces\MeetingRepositoryInterface;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberRepositoryInterface;
use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionRepositoryInterface;
use Unity\Positions\Interfaces\PositionViewFactoryInterface;
use RuntimeException;
use function register_deactivation_hook;

/**
 * Main Plugin Class
 */
class Plugin
{
    private static ?DependencyContainer $container = null;

    /**
     * Initialize the plugin
     */
    public static function init(): void
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

        // Initialize core services
        self::$container->get(CacheInterface::class);
        self::$container->get(MeetingFactoryInterface::class);
        self::$container->get(MeetingRepositoryInterface::class);
        self::$container->get(GroupFactoryInterface::class);
        self::$container->get(GroupViewFactoryInterface::class);
        self::$container->get(GroupRepositoryInterface::class);
        self::$container->get(GroupChangeTracker::class);
        self::$container->get(PositionFactoryInterface::class);
        self::$container->get(PositionRepositoryInterface::class);
        self::$container->get(MemberFactoryInterface::class);
        self::$container->get(MemberRepositoryInterface::class);
        self::$container->get(PositionViewFactoryInterface::class);
        self::$container->get(IntergroupManager::class);
    }

    /**
     * Get the dependency container
     *
     * @return DependencyContainer
     * @throws RuntimeException If plugin is not initialized
     */
    public static function getContainer(): DependencyContainer
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
