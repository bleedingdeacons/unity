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
use Unity\Tests\TestCase;

/**
 * The group, meeting, location and position doubles.
 *
 * The companion to {@see DoublesTest}, which covers the member side. Same
 * principle: PHP enforces the contracts at class-load time, so what is worth
 * asserting here is the behaviour a signature check would not catch — the
 * fields these doubles *derive* rather than store, and the finders that model
 * real filtering rather than answering every call with the same set.
 *
 * @covers \Unity\Testing\Doubles\GroupStub
 * @covers \Unity\Testing\Doubles\InMemoryGroupRepository
 * @covers \Unity\Testing\Doubles\InMemoryMeetingRepository
 * @covers \Unity\Testing\Doubles\InMemoryPositionRepository
 * @covers \Unity\Testing\Doubles\LocationStub
 * @covers \Unity\Testing\Doubles\MeetingStub
 * @covers \Unity\Testing\Doubles\PositionStub
 */
final class DirectoryDoublesTest extends TestCase
{
    public function testEveryStubSatisfiesItsContractAndDefaultsEveryField(): void
    {
        self::assertInstanceOf(Group::class, new GroupStub());
        self::assertInstanceOf(Meeting::class, new MeetingStub());
        self::assertInstanceOf(Location::class, new LocationStub());
        self::assertInstanceOf(Position::class, new PositionStub());

        self::assertSame(0, (new GroupStub())->getId());
        self::assertSame([], (new GroupStub())->getMeetings());
        self::assertNull((new MeetingStub())->getLocation());
        self::assertNull((new LocationStub())->getLatitude());
        self::assertTrue((new PositionStub())->isValid());
    }

    /**
     * getMeetings() hands back hydrated Meeting objects, not ids. A double
     * that returned ids would let a consumer ship code that breaks against
     * the live repository.
     */
    public function testGroupStubReturnsMeetingObjects(): void
    {
        $group = new GroupStub(id: 3, meetings: [new MeetingStub(id: 10), new MeetingStub(id: 11)]);

        self::assertContainsOnlyInstancesOf(Meeting::class, $group->getMeetings());
        self::assertSame([10, 11], array_map(
            static fn (Meeting $meeting): int => $meeting->getId(),
            $group->getMeetings()
        ));
    }

    public function testMeetingStubDerivesTheDayNameFromTheDayNumber(): void
    {
        self::assertSame('Sunday', (new MeetingStub(day: 0))->getDayOfWeek());
        self::assertSame('Wednesday', (new MeetingStub(day: 3))->getDayOfWeek());
        self::assertSame('Saturday', (new MeetingStub(day: 6))->getDayOfWeek());
    }

    /**
     * Out of range yields an empty string rather than an error, matching how
     * the real implementations treat unset meta.
     */
    public function testMeetingStubReturnsAnEmptyDayNameForAnImpossibleDay(): void
    {
        self::assertSame('', (new MeetingStub(day: 9))->getDayOfWeek());
    }

    public function testLocationStubDerivesItsFormattedAddressFromItsParts(): void
    {
        $location = new LocationStub(
            address: '1 Example Street',
            city: 'Bristol',
            postalCode: 'BS1 1AA',
            country: 'GB'
        );

        self::assertSame('1 Example Street, Bristol, BS1 1AA, GB', $location->getFormattedAddress());
    }

    public function testLocationStubOmitsEmptyPartsFromTheFormattedAddress(): void
    {
        self::assertSame('Bristol', (new LocationStub(city: 'Bristol'))->getFormattedAddress());
        self::assertSame('', (new LocationStub())->getFormattedAddress());
    }

    public function testLocationStubReportsCoordinatesOnlyWhenBothArePresent(): void
    {
        self::assertFalse((new LocationStub())->hasCoordinates());
        self::assertFalse((new LocationStub(latitude: 51.45))->hasCoordinates());
        self::assertTrue((new LocationStub(latitude: 51.45, longitude: -2.58))->hasCoordinates());
    }

    // ── Repositories ──────────────────────────────────────────────────

    public function testGroupRepositoryReadsBackWhatItWasSeededWith(): void
    {
        $repository = new InMemoryGroupRepository([
            new GroupStub(id: 1, title: 'Monday Steps'),
            new GroupStub(id: 2, title: 'Harbourside'),
        ]);

        self::assertSame(2, $repository->count());
        self::assertSame('Harbourside', $repository->findById(2)?->getTitle());
        self::assertNull($repository->findById(99));
    }

    public function testGroupRepositoryRecordsWritesAndAppliesDeletes(): void
    {
        $repository = new InMemoryGroupRepository([new GroupStub(id: 1)]);
        $added = new GroupStub(id: 2);

        self::assertTrue($repository->save($added));
        self::assertTrue($repository->update($added));
        self::assertTrue($repository->delete(1));

        self::assertSame([$added], $repository->saved);
        self::assertSame([$added], $repository->updated);
        self::assertSame([1], $repository->deleted);
        self::assertNull($repository->findById(1));
    }

    public function testGroupRepositoryRejectsWritesWhenAskedTo(): void
    {
        $repository = new InMemoryGroupRepository([], rejectWrites: true);

        $this->expectException(LogicException::class);

        $repository->save(new GroupStub());
    }

    public function testPositionRepositoryReadsWritesAndRejects(): void
    {
        $repository = new InMemoryPositionRepository([new PositionStub(id: 1, longName: 'Treasurer')]);

        self::assertSame('Treasurer', $repository->findById(1)?->getLongName());
        self::assertTrue($repository->save(new PositionStub(id: 2)));
        self::assertCount(1, $repository->saved);

        $this->expectException(LogicException::class);

        (new InMemoryPositionRepository([], rejectWrites: true))->delete(1);
    }

    /**
     * Six distinct finders, and a consumer's whole job may be choosing
     * between them — so each has to filter for real.
     */
    public function testMeetingRepositoryFiltersByDayAndByMode(): void
    {
        $repository = $this->meetings();

        self::assertCount(2, $repository->findByDay(1));
        self::assertCount(1, $repository->findByDay(2));
        self::assertCount(1, $repository->findOnline());
        self::assertCount(2, $repository->findInPerson());
        self::assertSame(3, $repository->count());
    }

    /**
     * The one relation a Meeting does not expose, so the double takes it
     * explicitly rather than inventing a rule.
     */
    public function testMeetingRepositoryFiltersByTheSuppliedGroupMapping(): void
    {
        $repository = $this->meetings();

        self::assertSame([10, 12], array_map(
            static fn (Meeting $meeting): int => $meeting->getId(),
            $repository->findByGroupId(1)
        ));
        self::assertSame([], $repository->findByGroupId(2));
    }

    public function testMeetingRepositoryWithNoGroupMappingFindsNothingByGroup(): void
    {
        $repository = new InMemoryMeetingRepository([new MeetingStub(id: 10)]);

        // Honest default: no meeting belongs to any group. Inventing one would
        // make a consumer look correct against a relation that does not exist.
        self::assertSame([], $repository->findByGroupId(1));
    }

    public function testMeetingRepositoryDerivesTheLocationRelationFromTheMeeting(): void
    {
        $repository = $this->meetings();

        self::assertCount(2, $repository->findByLocationId(100));
        self::assertSame([], $repository->findByLocationId(999));
    }

    public function testMeetingRepositorySearchesNamesWithoutRegardToCase(): void
    {
        $repository = $this->meetings();

        self::assertCount(1, $repository->search('BEGINNERS'));
        self::assertCount(2, $repository->search('monday'));
        self::assertSame([], $repository->search('nothing here'));
    }

    private function meetings(): InMemoryMeetingRepository
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
}
