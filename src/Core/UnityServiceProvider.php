<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Contact\ContactFactory;
use Unity\Contact\Interfaces\ContactFactoryInterface;
use Unity\Core\Interfaces\CacheInterface;
use Unity\Groups\GroupChangeTracker;
use Unity\Groups\GroupViewFactory;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupRepositoryInterface;
use Unity\Groups\Interfaces\GroupViewFactoryInterface;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingFactoryInterface;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingRepositoryInterface;
use Unity\Locations\Interfaces\LocationFactoryInterface;
use Unity\Locations\Interfaces\LocationRepositoryInterface;
use Unity\Meetings\Interfaces\MeetingFactoryInterface;
use Unity\Meetings\Interfaces\MeetingRepositoryInterface;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberRepositoryInterface;
use Unity\Members\MemberChangeTracker;
use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionRepositoryInterface;
use Unity\Positions\Interfaces\PositionViewFactoryInterface;
use Unity\Positions\PositionChangeTracker;
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
//            return new MeetingFactory(
//                $c->get(ContactFactoryInterface::class),
//                $c->get(LocationRepositoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(MeetingFactoryInterface::class);
        });

        // Register Meeting Repository
        $container->register(MeetingRepositoryInterface::class, function (DependencyContainer $c) {
//            return new MeetingRepository(
//                $c->get(MeetingFactoryInterface::class),
//                $c->get(CacheInterface::class)
//            );
            throw new DependencyNotRegisteredException(MeetingRepositoryInterface::class);
        });

        // Register Group Factory
        $container->register(GroupFactoryInterface::class, function () {
//            return new GroupFactory();
            throw new DependencyNotRegisteredException(GroupFactoryInterface::class);
        });

        // Register Group Repository
        $container->register(GroupRepositoryInterface::class, function (DependencyContainer $c) {
//            return new GroupRepository(
//                $c->get(GroupFactoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(GroupRepositoryInterface::class);
        });

        // Register GroupChangeTracker
        $container->register(GroupChangeTracker::class, function (DependencyContainer $c) {
            return new GroupChangeTracker(
                $c->get(GroupRepositoryInterface::class)
            );
        });

        // Register MemberChangeTracker (requires TSML-for-Unity to provide implementation)
        $container->register(MemberChangeTracker::class, function (DependencyContainer $c) {
//            return new MemberChangeTracker(
//                $c->get(MemberRepositoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(MemberChangeTracker::class);
        });

        // Register PositionChangeTracker (requires TSML-for-Unity to provide implementation)
        $container->register(PositionChangeTracker::class, function (DependencyContainer $c) {
//            return new PositionChangeTracker(
//                $c->get(PositionRepositoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(PositionChangeTracker::class);
        });

        // Register Position Factory
        $container->register(PositionFactoryInterface::class, function () {
//            return new PositionFactory();
            throw new DependencyNotRegisteredException(PositionFactoryInterface::class);
        });

        // Register Position Repository
        $container->register(PositionRepositoryInterface::class, function (DependencyContainer $c) {
//            return new PositionRepository(
//                $c->get(PositionFactoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
        });

        // Register Member Factory
        $container->register(MemberFactoryInterface::class, function () {
//            return new MemberFactory();
            throw new DependencyNotRegisteredException(MemberFactoryInterface::class);
        });

        // Register Member Repository
        $container->register(MemberRepositoryInterface::class, function (DependencyContainer $c) {
//            return new MemberRepository(
//                $c->get(MemberFactoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);
        });

        // Register Intergroup Meeting Factory
        $container->register(IntergroupMeetingFactoryInterface::class, function () {
//            return new IntergroupMeetingFactory();
            throw new DependencyNotRegisteredException(IntergroupMeetingFactoryInterface::class);
        });

        // Register Intergroup Meeting Repository
        $container->register(IntergroupMeetingRepositoryInterface::class, function (DependencyContainer $c) {
//            return new IntergroupMeetingRepository(
//                $c->get(IntergroupMeetingFactoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(IntergroupMeetingRepositoryInterface::class);
        });

        // Register Position View Factory (requires TSML-for-Unity to provide implementation)
        $container->register(PositionViewFactoryInterface::class, function (DependencyContainer $c) {
//            return new PositionViewFactory(
//                $c->get(PositionRepositoryInterface::class),
//                $c->get(MemberRepositoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(PositionViewFactoryInterface::class);
        });

        // Register Group View Factory
        $container->register(GroupViewFactoryInterface::class, function (DependencyContainer $c) {
            return new GroupViewFactory(
                $c->get(GroupRepositoryInterface::class),
                $c->get(MeetingRepositoryInterface::class)
            );
//            throw new DependencyNotRegisteredException(GroupViewFactoryInterface::class);
        });

        // Register Location Factory
        $container->register(LocationFactoryInterface::class, function () {
//            return new LocationFactory();
            throw new DependencyNotRegisteredException(LocationFactoryInterface::class);
        });

        // Register Locations Repository (requires LocationFactoryInterface to be registered by TSML-for-Unity)
        $container->register(LocationRepositoryInterface::class, function (DependencyContainer $c) {
//            return new LocationRepository(
//                $c->get(LocationFactoryInterface::class)
//            );
            throw new DependencyNotRegisteredException(LocationRepositoryInterface::class);
        });
    }
}