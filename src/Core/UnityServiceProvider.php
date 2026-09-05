<?php

declare(strict_types=1);

namespace Unity\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Auth\Interfaces\PasswordCredentialRepository;
use Unity\Auth\WpdbPasswordCredentialRepository;
use Unity\Contacts\Interfaces\ContactFactory;
use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\Configuration;
use Unity\Core\Interfaces\Container;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\Groups\Interfaces\GroupFactory;
use Unity\Groups\Interfaces\GroupRepository;
use Unity\Groups\Interfaces\GroupViewFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingGroupAttendanceFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingGroupAttendanceRepository;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingOfficerAttendanceFactory;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingOfficerAttendanceRepository;
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
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void
    {
        // Register Cache
        $container->register(Cache::class, function () {

            return new WordPressCache();
        });

        $container->register(Configuration::class, function () {

            return new UnityConfiguration();
        });

        // The member password store.
        //
        // Registered here rather than left to the consuming site, unlike
        // every repository below: this one has an implementation Unity
        // ships, because it is backed by a table Unity owns rather than by
        // the site's own post types. Reach and Fellowship resolve it from
        // here instead of each keeping a private copy over a private table
        // — which is what they used to do, and why a password set in one
        // was invisible to the other.
        $container->register(PasswordCredentialRepository::class, function () {

            global $wpdb;

            return new WpdbPasswordCredentialRepository($wpdb);
        });
    }
}
