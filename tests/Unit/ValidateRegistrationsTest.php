<?php

declare(strict_types=1);

namespace Unity\Tests\Unit;

use RuntimeException;
use Throwable;
use Unity\Core\DependencyContainer;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\Groups\Interfaces\GroupFactory;
use Unity\Groups\Interfaces\GroupRepository;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingChangeTracker;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingRepository;
use Unity\Locations\Interfaces\LocationRepository;
use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Members\Interfaces\MemberChangeTracker;
use Unity\Members\Interfaces\MemberFactory;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Members\Interfaces\MemberRevisor;
use Unity\Plugin;
use Unity\Positions\Interfaces\PositionChangeTracker;
use Unity\Positions\Interfaces\PositionRepository;

/*
 * Tests for Plugin::validateRegistrations().
 *
 * This method spent its whole life uncalled, so nothing ever exercised it.
 * unity.php now calls it at the end of boot, which makes its contents load
 * bearing: anything listed as required is asserted against every install in
 * the wild. These tests pin the two properties that matter — that it names
 * what is genuinely missing, and that it stays quiet about bindings older
 * companion plugins do not provide.
 */

/**
 * Every binding validateRegistrations() treats as mandatory.
 */
const REQUIRED_SERVICES = [
    MemberRepository::class,
    MemberFactory::class,
    MemberChangeTracker::class,
    GroupRepository::class,
    GroupFactory::class,
    GroupChangeTracker::class,
    MeetingRepository::class,
    LocationRepository::class,
    PositionRepository::class,
    PositionChangeTracker::class,
    IntergroupMeetingRepository::class,
    IntergroupMeetingChangeTracker::class,
];

/**
 * A container with the given ids bound to throwaway objects.
 *
 * @param list<string> $ids
 */
function containerWith(array $ids): DependencyContainer
{
    $container = new DependencyContainer();

    foreach ($ids as $id) {
        $container->register($id, static fn (): object => new \stdClass());
    }

    return $container;
}

it('passes when every required service is registered', function () {
    $plugin = Plugin::create(containerWith(REQUIRED_SERVICES));

    // No exception is the whole assertion.
    expect(fn () => $plugin->validateRegistrations())->not->toThrow(Throwable::class);
});

it('names the service that is missing', function () {
    $withoutGroups = array_values(array_diff(REQUIRED_SERVICES, [GroupRepository::class]));

    $plugin = Plugin::create(containerWith($withoutGroups));

    $plugin->validateRegistrations();
})->throws(RuntimeException::class, GroupRepository::class);

it('lists every missing service, not just the first', function () {
    $plugin = Plugin::create(containerWith([]));

    expect(fn () => $plugin->validateRegistrations())->toThrow(function (RuntimeException $e) {
        foreach (REQUIRED_SERVICES as $id) {
            expect(str_contains($e->getMessage(), $id))
                ->toBeTrue("Missing service $id was not reported.");
        }
    });
});

// MemberRevisor arrived in Unity after tsml-for-unity had already shipped,
// so only newer versions bind it. Requiring it would report a correctly
// configured site running an older companion plugin as broken — and since
// unity.php surfaces the result as an admin error notice, that lands in
// front of the site owner as a false alarm on every page load.
it('does not require MemberRevisor', function () {
    // Everything mandatory is present; MemberRevisor deliberately is not.
    $container = containerWith(REQUIRED_SERVICES);

    expect($container->has(MemberRevisor::class))
        ->toBeFalse('Guard precondition: MemberRevisor must be absent for this test to mean anything.');

    expect(fn () => Plugin::create($container)->validateRegistrations())->not->toThrow(Throwable::class);
});
