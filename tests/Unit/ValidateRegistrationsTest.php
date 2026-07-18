<?php

declare(strict_types=1);

namespace Unity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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

/**
 * Tests for Plugin::validateRegistrations().
 *
 * This method spent its whole life uncalled, so nothing ever exercised it.
 * unity.php now calls it at the end of boot, which makes its contents load
 * bearing: anything listed as required is asserted against every install in
 * the wild. These tests pin the two properties that matter — that it names
 * what is genuinely missing, and that it stays quiet about bindings older
 * companion plugins do not provide.
 */
class ValidateRegistrationsTest extends TestCase
{
    /**
     * Every binding validateRegistrations() treats as mandatory.
     */
    private const REQUIRED = [
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
    private function containerWith(array $ids): DependencyContainer
    {
        $container = new DependencyContainer();

        foreach ($ids as $id) {
            $container->register($id, static fn (): object => new \stdClass());
        }

        return $container;
    }

    /**
     * @test
     */
    public function it_passes_when_every_required_service_is_registered(): void
    {
        $plugin = Plugin::create($this->containerWith(self::REQUIRED));

        $plugin->validateRegistrations();

        // No exception. Assert explicitly so the test is not risky.
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_names_the_service_that_is_missing(): void
    {
        $withoutGroups = array_values(array_diff(self::REQUIRED, [GroupRepository::class]));

        $plugin = Plugin::create($this->containerWith($withoutGroups));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote(GroupRepository::class, '/') . '/');

        $plugin->validateRegistrations();
    }

    /**
     * @test
     */
    public function it_lists_every_missing_service_not_just_the_first(): void
    {
        $plugin = Plugin::create($this->containerWith([]));

        try {
            $plugin->validateRegistrations();
            $this->fail('Expected a RuntimeException when nothing is registered.');
        } catch (RuntimeException $e) {
            foreach (self::REQUIRED as $id) {
                $this->assertStringContainsString(
                    $id,
                    $e->getMessage(),
                    "Missing service $id was not reported."
                );
            }
        }
    }

    /**
     * MemberRevisor arrived in Unity after tsml-for-unity had already shipped,
     * so only newer versions bind it. Requiring it would report a correctly
     * configured site running an older companion plugin as broken — and since
     * unity.php surfaces the result as an admin error notice, that lands in
     * front of the site owner as a false alarm on every page load.
     *
     * @test
     */
    public function it_does_not_require_member_revisor(): void
    {
        // Everything mandatory is present; MemberRevisor deliberately is not.
        $container = $this->containerWith(self::REQUIRED);

        $this->assertFalse(
            $container->has(MemberRevisor::class),
            'Guard precondition: MemberRevisor must be absent for this test to mean anything.'
        );

        Plugin::create($container)->validateRegistrations();

        $this->assertTrue(true);
    }
}
