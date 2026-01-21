<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Common\Interfaces\CacheInterface;
use Unity\Common\WordPressCache;
use Unity\Contact\ContactFactory;
use Unity\Contact\Interfaces\ContactFactoryInterface;
use Unity\Groups\GroupChangeTracker;
use Unity\Groups\GroupFactory;
use Unity\Groups\GroupRepository;
use Unity\Groups\GroupViewFactory;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupRepositoryInterface;
use Unity\Groups\Interfaces\GroupViewFactoryInterface;
use Unity\Intergroup\IntergroupManager;
use Unity\Locations\Interfaces\LocationFactoryInterface;
use Unity\Locations\Interfaces\LocationRepositoryInterface;
use Unity\Locations\LocationFactory;
use Unity\Locations\LocationRepository;
use Unity\Meetings\Interfaces\MeetingFactoryInterface;
use Unity\Meetings\Interfaces\MeetingRepositoryInterface;
use Unity\Meetings\MeetingFactory;
use Unity\Meetings\MeetingRepository;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberRepositoryInterface;
use Unity\Members\MemberFactory;
use Unity\Members\MemberRepository;
use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionRepositoryInterface;
use Unity\Positions\Interfaces\PositionViewFactoryInterface;
use Unity\Positions\PositionFactory;
use Unity\Positions\PositionRepository;
use Unity\Positions\PositionViewFactory;

/**
 * Class UnityServiceProvider
 * 
 * Registers all plugin services
 */
class UnityServiceProvider
{
    /**
     * Register all services in the container
     *
     * @param DependencyContainer $container
     * @return void
     */
    public function register(DependencyContainer $container): void
    {
        // Register Cache
        $container->register(CacheInterface::class, function () {
            return new WordPressCache();
        });

        // Register Contact Factory
        $container->register(ContactFactoryInterface::class, function () {
            return new ContactFactory();
        });

        // Register Meeting Factory
        $container->register(MeetingFactoryInterface::class, function (DependencyContainer $c) {
            return new MeetingFactory(
                $c->get(ContactFactoryInterface::class),
                $c->get(LocationRepositoryInterface::class)
            );
        });

        // Register Meeting Repository
        $container->register(MeetingRepositoryInterface::class, function (DependencyContainer $c) {
            return new MeetingRepository(
                $c->get(MeetingFactoryInterface::class),
                $c->get(CacheInterface::class)
            );
        });

        // Register Group Factory
        $container->register(GroupFactoryInterface::class, function () {
            return new GroupFactory();
        });

        // Register Group Repository
        $container->register(GroupRepositoryInterface::class, function (DependencyContainer $c) {
            return new GroupRepository(
                $c->get(GroupFactoryInterface::class)
            );
        });

        // Register GroupChangeTracker
        $container->register(GroupChangeTracker::class, function (DependencyContainer $c) {
            return new GroupChangeTracker(
                $c->get(GroupRepositoryInterface::class)
            );
        });

        // Register Position Factory
        $container->register(PositionFactoryInterface::class, function () {
            return new PositionFactory();
        });

        // Register Position Repository
        $container->register(PositionRepositoryInterface::class, function (DependencyContainer $c) {
            return new PositionRepository(
                $c->get(PositionFactoryInterface::class)
            );
        });

        // Register Member Factory
        $container->register(MemberFactoryInterface::class, function () {
            return new MemberFactory();
        });

        // Register Member Repository
        $container->register(MemberRepositoryInterface::class, function (DependencyContainer $c) {
            return new MemberRepository(
                $c->get(MemberFactoryInterface::class)
            );
        });

        // Register Position View Factory
        $container->register(PositionViewFactoryInterface::class, function (DependencyContainer $c) {
            return new PositionViewFactory(
                $c->get(PositionRepositoryInterface::class),
                $c->get(MemberRepositoryInterface::class)
            );
        });

        // Register Group View Factory
        $container->register(GroupViewFactoryInterface::class, function (DependencyContainer $c) {
            return new GroupViewFactory(
                $c->get(GroupRepositoryInterface::class),
                $c->get(MeetingRepositoryInterface::class)
            );
        });

        // Register Intergroup Manager
        $container->register(IntergroupManager::class, function (DependencyContainer $c) {
            return new IntergroupManager(
                $c->get(PositionViewFactoryInterface::class)
            );
        });

        // Register Location Factory
        $container->register(LocationFactoryInterface::class, function () {
            return new LocationFactory();
        });

        // Register Locations Repository (requires LocationFactoryInterface to be registered by TSML-for-Unity)
        $container->register(LocationRepositoryInterface::class, function (DependencyContainer $c) {
            return new LocationRepository(
                $c->get(LocationFactoryInterface::class)
            );
        });
    }
}
