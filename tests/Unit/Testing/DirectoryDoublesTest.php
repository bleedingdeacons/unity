<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Testing;

use LogicException;
use Unity\Groups\Interfaces\Group;
use Unity\Locations\Interfaces\Location;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Positions\Interfaces\Position;
use Unity\Testing\Doubles\GroupStub;
use Unity\Testing\Doubles\InMemoryGroupRepository;
use Unity\Testing\Doubles\InMemoryMeetingRepository;
use Unity\Testing\Doubles\InMemoryPositionRepository;
use Unity\Testing\Doubles\LocationStub;
use Unity\Testing\Doubles\MeetingStub;
use Unity\Testing\Doubles\PositionStub;

/*
 * The group, meeting, location and position doubles.
 *
 * The companion to DoublesTest.php, which covers the member side. Same
 * principle: PHP enforces the contracts at class-load time, so what is worth
 * asserting here is the behaviour a signature check would not catch — the
 * fields these doubles *derive* rather than store, and the finders that model
 * real filtering rather than answering every call with the same set.
 */

// No covers(): src/Testing is excluded from coverage in phpunit.xml, so naming
// those classes as covered targets attributes nothing and PHPUnit 13 rejects
// them outright. The @covers these replace had the same problem; it was
// simply never validated. The PHPUnit class carried #[CoversNothing]; Pest has
// no coversNothing(), so these tests now attribute whatever non-excluded
// source they happen to run.

function directoryMeetings(): InMemoryMeetingRepository
{
    $hall = new LocationStub(id: 100, name: 'Church Hall');

    return new InMemoryMeetingRepository(
        [
            new MeetingStub(id: 10, name: 'Monday Steps', location: $hall, day: 1),
            new MeetingStub(id: 11, name: 'Tuesday Big Book', day: 2, online: true),
            new MeetingStub(id: 12, name: 'Monday Beginners', location: $hall, day: 1),
        ],
        [10 => 1, 12 => 1]
    );
}

describe('stubs', function () {
    it('satisfies every contract and defaults every field', function () {
        expect(new GroupStub())->toBeInstanceOf(Group::class)
            ->and(new MeetingStub())->toBeInstanceOf(Meeting::class)
            ->and(new LocationStub())->toBeInstanceOf(Location::class)
            ->and(new PositionStub())->toBeInstanceOf(Position::class)
            ->and((new GroupStub())->getId())->toBe(0)
            ->and((new GroupStub())->getMeetings())->toBe([])
            ->and((new MeetingStub())->getLocation())->toBeNull()
            ->and((new LocationStub())->getLatitude())->toBeNull()
            ->and((new PositionStub())->isValid())->toBeTrue();
    });

    // getMeetings() hands back hydrated Meeting objects, not ids. A double
    // that returned ids would let a consumer ship code that breaks against
    // the live repository.
    it('returns meeting objects from a group stub', function () {
        $group = new GroupStub(id: 3, meetings: [new MeetingStub(id: 10), new MeetingStub(id: 11)]);

        expect($group->getMeetings())->toContainOnlyInstancesOf(Meeting::class)
            ->and(array_map(
                static fn (Meeting $meeting): int => $meeting->getId(),
                $group->getMeetings()
            ))->toBe([10, 11]);
    });

    it('derives the day name from the day number on a meeting stub', function () {
        expect((new MeetingStub(day: 0))->getDayOfWeek())->toBe('Sunday')
            ->and((new MeetingStub(day: 3))->getDayOfWeek())->toBe('Wednesday')
            ->and((new MeetingStub(day: 6))->getDayOfWeek())->toBe('Saturday');
    });

    // Out of range yields an empty string rather than an error, matching how
    // the real implementations treat unset meta.
    it('returns an empty day name for an impossible day on a meeting stub', function () {
        expect((new MeetingStub(day: 9))->getDayOfWeek())->toBe('');
    });

    it('derives its formatted address from its parts on a location stub', function () {
        $location = new LocationStub(
            address: '1 Example Street',
            city: 'Bristol',
            postalCode: 'BS1 1AA',
            country: 'GB'
        );

        expect($location->getFormattedAddress())->toBe('1 Example Street, Bristol, BS1 1AA, GB');
    });

    it('omits empty parts from the formatted address on a location stub', function () {
        expect((new LocationStub(city: 'Bristol'))->getFormattedAddress())->toBe('Bristol')
            ->and((new LocationStub())->getFormattedAddress())->toBe('');
    });

    it('reports coordinates only when both are present on a location stub', function () {
        expect((new LocationStub())->hasCoordinates())->toBeFalse()
            ->and((new LocationStub(latitude: 51.45))->hasCoordinates())->toBeFalse()
            ->and((new LocationStub(latitude: 51.45, longitude: -2.58))->hasCoordinates())->toBeTrue();
    });
});

// ── Repositories ──────────────────────────────────────────────────

describe('repositories', function () {
    it('reads back what the group repository was seeded with', function () {
        $repository = new InMemoryGroupRepository([
            new GroupStub(id: 1, title: 'Monday Steps'),
            new GroupStub(id: 2, title: 'Harbourside'),
        ]);

        expect($repository->count())->toBe(2)
            ->and($repository->findById(2)?->getTitle())->toBe('Harbourside')
            ->and($repository->findById(99))->toBeNull();
    });

    it('records writes and applies deletes in the group repository', function () {
        $repository = new InMemoryGroupRepository([new GroupStub(id: 1)]);
        $added = new GroupStub(id: 2);

        expect($repository->save($added))->toBeTrue()
            ->and($repository->update($added))->toBeTrue()
            ->and($repository->delete(1))->toBeTrue();

        expect($repository->saved)->toBe([$added])
            ->and($repository->updated)->toBe([$added])
            ->and($repository->deleted)->toBe([1])
            ->and($repository->findById(1))->toBeNull();
    });

    it('rejects writes in the group repository when asked to', function () {
        $repository = new InMemoryGroupRepository([], rejectWrites: true);

        $repository->save(new GroupStub());
    })->throws(LogicException::class);

    it('reads, writes and rejects in the position repository', function () {
        $repository = new InMemoryPositionRepository([new PositionStub(id: 1, longName: 'Treasurer')]);

        expect($repository->findById(1)?->getLongName())->toBe('Treasurer')
            ->and($repository->save(new PositionStub(id: 2)))->toBeTrue()
            ->and($repository->saved)->toHaveCount(1);

        expect(fn () => (new InMemoryPositionRepository([], rejectWrites: true))->delete(1))
            ->toThrow(LogicException::class);
    });

    // Six distinct finders, and a consumer's whole job may be choosing
    // between them — so each has to filter for real.
    it('filters by day and by mode in the meeting repository', function () {
        $repository = directoryMeetings();

        expect($repository->findByDay(1))->toHaveCount(2)
            ->and($repository->findByDay(2))->toHaveCount(1)
            ->and($repository->findOnline())->toHaveCount(1)
            ->and($repository->findInPerson())->toHaveCount(2)
            ->and($repository->count())->toBe(3);
    });

    // The one relation a Meeting does not expose, so the double takes it
    // explicitly rather than inventing a rule.
    it('filters by the supplied group mapping in the meeting repository', function () {
        $repository = directoryMeetings();

        expect(array_map(
            static fn (Meeting $meeting): int => $meeting->getId(),
            $repository->findByGroupId(1)
        ))->toBe([10, 12])
            ->and($repository->findByGroupId(2))->toBe([]);
    });

    it('finds nothing by group in a meeting repository with no group mapping', function () {
        $repository = new InMemoryMeetingRepository([new MeetingStub(id: 10)]);

        // Honest default: no meeting belongs to any group. Inventing one would
        // make a consumer look correct against a relation that does not exist.
        expect($repository->findByGroupId(1))->toBe([]);
    });

    it('derives the location relation from the meeting in the meeting repository', function () {
        $repository = directoryMeetings();

        expect($repository->findByLocationId(100))->toHaveCount(2)
            ->and($repository->findByLocationId(999))->toBe([]);
    });

    it('searches names without regard to case in the meeting repository', function () {
        $repository = directoryMeetings();

        expect($repository->search('BEGINNERS'))->toHaveCount(1)
            ->and($repository->search('monday'))->toHaveCount(2)
            ->and($repository->search('nothing here'))->toBe([]);
    });
});
