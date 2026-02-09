<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Contacts\Interfaces\ContactFactory;
use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\Configuration;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\Groups\Interfaces\GroupFactory;
use Unity\Groups\Interfaces\GroupRepository;
use Unity\Groups\Interfaces\GroupViewFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingRepository;
use Unity\Locations\Interfaces\LocationFactory;
use Unity\Locations\Interfaces\LocationRepository;
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
        $container->register(Cache::class, function () {

            return new WordPressCache();

        });

        $container->register(Configuration::class, function () {

            return new UnityConfiguration();

        });


        // Register Contacts Factory
        $container->register(ContactFactory::class, function () {

            throw new DependencyNotRegisteredException(ContactFactory::class);

        });

        // Register Meeting Factory
        $container->register(MeetingFactory::class, function (DependencyContainer $c) {
//            return new MeetingFactory(
//                $c->get(ContactFactory::class),
//                $c->get(LocationRepository::class)
//            );
            throw new DependencyNotRegisteredException(MeetingFactory::class);

        });

        // Register Meeting Repository
        $container->register(MeetingRepository::class, function (DependencyContainer $c) {
//            return new MeetingRepository(
//                $c->get(MeetingFactory::class),
//                $c->get(Cache::class)
//            );
            throw new DependencyNotRegisteredException(MeetingRepository::class);

        });

        // Register Group Factory
        $container->register(GroupFactory::class, function () {
//            return new GroupFactory();
            throw new DependencyNotRegisteredException(GroupFactory::class);
        });

        // Register Group Repository
        $container->register(GroupRepository::class, function (DependencyContainer $c) {
//            return new GroupRepository(
//                $c->get(GroupFactory::class)
//            );
            throw new DependencyNotRegisteredException(GroupRepository::class);

        });

        // Register GroupChangeTracker (requires TSML-for-Unity to provide implementation)
        $container->register(GroupChangeTracker::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(GroupChangeTracker::class);

        });

        // Register MemberChangeTracker (requires TSML-for-Unity to provide implementation)
        $container->register(MemberChangeTracker::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(MemberChangeTracker::class);

        });

        // Register PositionChangeTracker (requires TSML-for-Unity to provide implementation)
        $container->register(PositionChangeTracker::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(PositionChangeTracker::class);

        });

        // Register Position Factory
        $container->register(PositionFactory::class, function () {

            throw new DependencyNotRegisteredException(PositionFactory::class);

        });

        // Register Position Repository
        $container->register(PositionRepository::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(PositionRepository::class);

        });

        // Register Member Factory
        $container->register(MemberFactory::class, function () {

            throw new DependencyNotRegisteredException(MemberFactory::class);

        });

        // Register Member Repository
        $container->register(MemberRepository::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(MemberRepository::class);

        });

        // Register Intergroup Meeting Factory
        $container->register(IntergroupMeetingFactory::class, function () {
//            return new IntergroupMeetingFactory();
            throw new DependencyNotRegisteredException(IntergroupMeetingFactory::class);

        });

        // Register Intergroup Meeting Repository
        $container->register(IntergroupMeetingRepository::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(IntergroupMeetingRepository::class);

        });

        // Register Position View Factory (requires TSML-for-Unity to provide implementation)
        $container->register(PositionViewFactory::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(PositionViewFactory::class);

        });

        // Register Group View Factory
        $container->register(GroupViewFactory::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(GroupViewFactory::class);

        });

        // Register Location Factory
        $container->register(LocationFactory::class, function () {

            throw new DependencyNotRegisteredException(LocationFactory::class);

        });

        // Register Locations Repository (requires LocationFactory to be registered by TSML-for-Unity)
        $container->register(LocationRepository::class, function (DependencyContainer $c) {

            throw new DependencyNotRegisteredException(LocationRepository::class);

        });
    }
}