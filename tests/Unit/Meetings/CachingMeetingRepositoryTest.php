<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Meetings;

use PHPUnit\Framework\Attributes\Test;
use Unity\Meetings\CachingMeetingRepository;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMeetingRepository;
use Unity\Testing\Doubles\LocationStub;
use Unity\Testing\Doubles\MeetingStub;
use Unity\Tests\TestCase;

/**
 * Tests for {@see CachingMeetingRepository}.
 *
 * MeetingRepository is read-only, so this decorator cannot invalidate itself:
 * everything here either proves a read is served from the cache, or proves a
 * bump makes it stop being.
 */
class CachingMeetingRepositoryTest extends TestCase
{
    private CountingMeetingRepository $inner;
    private InMemoryCache $cache;
    private CachingMeetingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = new CountingMeetingRepository(new MutableMeetingRepository([
            new MeetingStub(id: 1, name: 'Monday Lunchtime', day: 1, online: false),
            new MeetingStub(id: 2, name: 'Monday Evening', day: 1, online: true),
            new MeetingStub(id: 3, name: 'Tuesday Evening', day: 2, online: false),
        ]));

        $this->cache = new InMemoryCache();
        $this->repository = new CachingMeetingRepository($this->inner, $this->cache);
    }

    #[Test]
    public function it_is_a_meeting_repository(): void
    {
        $this->assertInstanceOf(MeetingRepository::class, $this->repository);
    }

    #[Test]
    public function a_second_read_of_the_same_meeting_does_not_reach_the_repository(): void
    {
        $first = $this->repository->findById(1);
        $second = $this->repository->findById(1);

        $this->assertSame($first, $second);
        $this->assertSame(1, $this->inner->findByIdCalls);
    }

    #[Test]
    public function an_absent_meeting_is_not_cached_and_an_impossible_id_costs_nothing(): void
    {
        $this->assertNull($this->repository->findById(99));
        $this->assertNull($this->repository->findById(99));
        $this->assertSame(2, $this->inner->findByIdCalls);

        $this->assertNull($this->repository->findById(0));
        $this->assertSame(2, $this->inner->findByIdCalls);
    }

    #[Test]
    public function a_cached_listing_costs_one_round_trip(): void
    {
        $this->repository->findAll();
        $this->cache->multiGets = 0;

        $this->assertCount(3, $this->repository->findAll());
        $this->assertSame(1, $this->inner->findAllCalls);
        $this->assertSame(1, $this->cache->multiGets);
    }

    #[Test]
    public function each_day_is_cached_under_its_own_key(): void
    {
        $monday = $this->repository->findByDay(1);
        $tuesday = $this->repository->findByDay(2);

        $this->repository->findByDay(1);
        $this->repository->findByDay(2);

        $this->assertSame([1, 2], $this->ids($monday));
        $this->assertSame([3], $this->ids($tuesday));
        $this->assertSame(2, $this->inner->findByDayCalls);
    }

    #[Test]
    public function the_online_and_in_person_listings_do_not_share_an_entry(): void
    {
        $online = $this->repository->findOnline();
        $inPerson = $this->repository->findInPerson();

        $this->repository->findOnline();
        $this->repository->findInPerson();

        $this->assertSame([2], $this->ids($online));
        $this->assertSame([1, 3], $this->ids($inPerson));
        $this->assertSame(1, $this->inner->findOnlineCalls);
        $this->assertSame(1, $this->inner->findInPersonCalls);
    }

    #[Test]
    public function a_filtered_call_is_passed_straight_through(): void
    {
        $this->repository->findByDay(1, ['posts_per_page' => 1]);
        $this->repository->findByDay(1, ['posts_per_page' => 1]);
        $this->repository->findAll(['orderby' => 'title']);
        $this->repository->findAll(['orderby' => 'title']);
        $this->repository->findOnline(['posts_per_page' => 1]);
        $this->repository->findOnline(['posts_per_page' => 1]);
        $this->repository->findInPerson(['posts_per_page' => 1]);
        $this->repository->findInPerson(['posts_per_page' => 1]);

        $this->assertSame(2, $this->inner->findByDayCalls);
        $this->assertSame(2, $this->inner->findAllCalls);
        $this->assertSame(2, $this->inner->findOnlineCalls);
        $this->assertSame(2, $this->inner->findInPersonCalls);
    }

    #[Test]
    public function a_search_is_never_cached(): void
    {
        $this->repository->search('monday');
        $this->repository->search('monday');

        // The keyword space is unbounded; caching it would mostly store
        // one-off misses.
        $this->assertSame(2, $this->inner->searchCalls);
    }

    #[Test]
    public function an_unfiltered_count_is_cached_and_a_filtered_one_is_not(): void
    {
        $this->repository->count();
        $this->repository->count();
        $this->repository->count(['post_status' => 'draft']);

        $this->assertSame(2, $this->inner->countCalls);
    }

    #[Test]
    public function a_bump_drops_every_listing_and_every_meeting(): void
    {
        $this->repository->findAll();
        $this->repository->findByDay(1);
        $this->repository->findById(1);

        $this->repository->bump();

        $this->repository->findAll();
        $this->repository->findByDay(1);

        $this->assertSame(2, $this->inner->findAllCalls);
        $this->assertSame(2, $this->inner->findByDayCalls);
    }

    #[Test]
    public function an_edited_meeting_is_served_new_once_the_cache_is_bumped(): void
    {
        $this->assertSame('Monday Lunchtime', $this->repository->findById(1)?->getName());

        // What editing a meeting in the admin does: the row changes without
        // the repository knowing, and PostTypeCacheInvalidator calls bump().
        $this->inner->inner()->replace(new MeetingStub(id: 1, name: 'Monday Noon', day: 1));
        $this->repository->bump();

        $this->assertSame('Monday Noon', $this->repository->findById(1)?->getName());
    }

    #[Test]
    public function an_evicted_version_does_not_resurrect_a_stale_meeting(): void
    {
        $this->repository->findById(1);

        $this->inner->inner()->replace(new MeetingStub(id: 1, name: 'Monday Noon', day: 1));
        $this->repository->bump();

        // Memcached discards entries under memory pressure, the version
        // counter included. Regenerating it from a fixed starting point would
        // put the pre-bump entries back in reach.
        $this->cache->evict('meetings_version', 'unity_meetings');

        $this->assertSame('Monday Noon', $this->repository->findById(1)?->getName());
    }

    #[Test]
    public function meetings_evicted_from_a_cached_listing_are_re_read_in_one_query(): void
    {
        $this->repository->findAll();

        $this->inner->findAllCalls = 0;
        $this->evictMeeting(1);
        $this->evictMeeting(3);

        $meetings = $this->repository->findAll();

        $this->assertSame([1, 2, 3], $this->ids($meetings));
        $this->assertSame(1, $this->inner->findAllCalls);
        $this->assertSame(0, $this->inner->findByIdCalls);
    }

    #[Test]
    public function a_meeting_deleted_behind_the_decorator_drops_out_of_a_cached_listing(): void
    {
        $this->repository->findAll();

        $this->inner->inner()->remove(2);
        $this->evictMeeting(2);

        $this->assertSame([1, 3], $this->ids($this->repository->findAll()));
    }

    #[Test]
    public function an_empty_listing_is_cached_and_asks_the_cache_for_nothing(): void
    {
        $inner = new CountingMeetingRepository(new MutableMeetingRepository([]));
        $repository = new CachingMeetingRepository($inner, $this->cache);

        $this->assertSame([], $repository->findAll());

        $this->cache->multiGets = 0;

        $this->assertSame([], $repository->findAll());
        $this->assertSame(1, $inner->findAllCalls);
        $this->assertSame(0, $this->cache->multiGets);
    }

    #[Test]
    public function cached_meetings_expire_even_when_nothing_bumps_them(): void
    {
        $this->repository->findById(1);

        $expiry = $this->cache->expiries[array_key_last($this->cache->expiries)] ?? 0;

        $this->assertGreaterThan(0, $expiry);
        $this->assertLessThanOrEqual(3600, $expiry);
    }

    #[Test]
    public function each_group_is_cached_under_its_own_key(): void
    {
        $inner = new CountingMeetingRepository(new MutableMeetingRepository([
            new MeetingStub(id: 1, name: 'Monday Lunchtime', day: 1),
            new MeetingStub(id: 2, name: 'Tuesday Evening', day: 2),
        ], [1 => 10, 2 => 11]));
        $repository = new CachingMeetingRepository($inner, $this->cache);

        $ten = $repository->findByGroupId(10);
        $repository->findByGroupId(10);
        $repository->findByGroupId(11);
        $repository->findByGroupId(10, ['posts_per_page' => 1]);

        $this->assertSame([1], $this->ids($ten));

        // One per group, plus the filtered call, which is never cached.
        $this->assertSame(3, $inner->findByGroupIdCalls);
    }

    #[Test]
    public function each_location_is_cached_under_its_own_key(): void
    {
        $inner = new CountingMeetingRepository(new MutableMeetingRepository([
            new MeetingStub(id: 1, name: 'Church Hall', location: new LocationStub(id: 20)),
            new MeetingStub(id: 2, name: 'Community Centre', location: new LocationStub(id: 21)),
        ]));
        $repository = new CachingMeetingRepository($inner, $this->cache);

        $twenty = $repository->findByLocationId(20);
        $repository->findByLocationId(20);
        $repository->findByLocationId(21);
        $repository->findByLocationId(20, ['orderby' => 'title']);

        $this->assertSame([1], $this->ids($twenty));
        $this->assertSame(3, $inner->findByLocationIdCalls);
    }

    /**
     * @param array<int, Meeting> $meetings
     * @return array<int, int>
     */
    private function ids(array $meetings): array
    {
        return array_map(static fn (Meeting $meeting): int => $meeting->getId(), $meetings);
    }

    private function evictMeeting(int $id): void
    {
        $version = $this->cache->get('meetings_version', 'unity_meetings');

        $this->cache->evict('meeting_' . $id . ':' . (is_string($version) ? $version : ''), 'unity_meetings');
    }
}

/**
 * A MeetingRepository that counts the reads reaching it.
 *
 * Wraps the shared double rather than reimplementing it, so what sits under
 * the counters is the behaviour every other consumer's tests see.
 */
final class CountingMeetingRepository implements MeetingRepository
{
    public int $findByIdCalls = 0;
    public int $findAllCalls = 0;
    public int $findByDayCalls = 0;
    public int $findOnlineCalls = 0;
    public int $findInPersonCalls = 0;
    public int $findByGroupIdCalls = 0;
    public int $findByLocationIdCalls = 0;
    public int $searchCalls = 0;
    public int $countCalls = 0;

    public function __construct(private MutableMeetingRepository $inner)
    {
    }

    public function inner(): MutableMeetingRepository
    {
        return $this->inner;
    }

    public function findById(int $id): ?Meeting
    {
        $this->findByIdCalls++;

        return $this->inner->findById($id);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findAll(array $args = []): array
    {
        $this->findAllCalls++;

        return $this->inner->findAll($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByDay(int $day, array $args = []): array
    {
        $this->findByDayCalls++;

        return $this->inner->findByDay($day, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findOnline(array $args = []): array
    {
        $this->findOnlineCalls++;

        return $this->inner->findOnline($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findInPerson(array $args = []): array
    {
        $this->findInPersonCalls++;

        return $this->inner->findInPerson($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByGroupId(int $groupId, array $args = []): array
    {
        $this->findByGroupIdCalls++;

        return $this->inner->findByGroupId($groupId, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByLocationId(int $locationId, array $args = []): array
    {
        $this->findByLocationIdCalls++;

        return $this->inner->findByLocationId($locationId, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function search(string $keyword, array $args = []): array
    {
        $this->searchCalls++;

        return $this->inner->search($keyword, $args);
    }

    /** @param array<string, mixed> $args */
    public function count(array $args = []): int
    {
        $this->countCalls++;

        return $this->inner->count($args);
    }
}

/**
 * The shared meeting double plus the two writes these tests need.
 *
 * MeetingRepository is read-only, so the double has no writes to offer — but
 * a cache is only interesting when the underlying data moves, and the whole
 * point here is what happens when it moves without the repository knowing.
 * Kept local rather than pushed into the shared double, which should model
 * the contract and nothing else.
 */
final class MutableMeetingRepository implements MeetingRepository
{
    private InMemoryMeetingRepository $meetings;

    /** @var array<int, Meeting> */
    private array $seed;

    /** @var array<int, int> Meeting id to group id, as the shared double takes it. */
    private array $groupByMeetingId;

    /**
     * @param array<int, Meeting> $seed
     * @param array<int, int> $groupByMeetingId
     */
    public function __construct(array $seed, array $groupByMeetingId = [])
    {
        $this->seed = array_values($seed);
        $this->groupByMeetingId = $groupByMeetingId;
        $this->meetings = new InMemoryMeetingRepository($this->seed, $this->groupByMeetingId);
    }

    public function replace(Meeting $meeting): void
    {
        foreach ($this->seed as $index => $existing) {
            if ($existing->getId() === $meeting->getId()) {
                $this->seed[$index] = $meeting;
            }
        }

        $this->meetings = new InMemoryMeetingRepository($this->seed, $this->groupByMeetingId);
    }

    public function remove(int $id): void
    {
        $this->seed = array_values(array_filter(
            $this->seed,
            static fn (Meeting $meeting): bool => $meeting->getId() !== $id
        ));

        $this->meetings = new InMemoryMeetingRepository($this->seed, $this->groupByMeetingId);
    }

    public function findById(int $id): ?Meeting
    {
        return $this->meetings->findById($id);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findAll(array $args = []): array
    {
        return $this->meetings->findAll($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByDay(int $day, array $args = []): array
    {
        return $this->meetings->findByDay($day, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findOnline(array $args = []): array
    {
        return $this->meetings->findOnline($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findInPerson(array $args = []): array
    {
        return $this->meetings->findInPerson($args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByGroupId(int $groupId, array $args = []): array
    {
        return $this->meetings->findByGroupId($groupId, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByLocationId(int $locationId, array $args = []): array
    {
        return $this->meetings->findByLocationId($locationId, $args);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function search(string $keyword, array $args = []): array
    {
        return $this->meetings->search($keyword, $args);
    }

    /** @param array<string, mixed> $args */
    public function count(array $args = []): int
    {
        return $this->meetings->count($args);
    }
}
