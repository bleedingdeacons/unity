<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Meetings;

use Unity\Meetings\CachingMeetingRepository;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMeetingRepository;
use Unity\Testing\Doubles\LocationStub;
use Unity\Testing\Doubles\MeetingStub;

/*
 * Tests for {@see CachingMeetingRepository}.
 *
 * MeetingRepository is read-only, so this decorator cannot invalidate itself:
 * everything here either proves a read is served from the cache, or proves a
 * bump makes it stop being.
 */

/**
 * @param array<int, Meeting> $meetings
 * @return array<int, int>
 */
function meetingIds(array $meetings): array
{
    return array_map(static fn (Meeting $meeting): int => $meeting->getId(), $meetings);
}

function evictMeeting(InMemoryCache $cache, int $id): void
{
    $version = $cache->get('meetings_version', 'unity_meetings');

    $cache->evict('meeting_' . $id . ':' . (is_string($version) ? $version : ''), 'unity_meetings');
}

beforeEach(function () {
    $this->inner = new CountingMeetingRepository(new MutableMeetingRepository([
        new MeetingStub(id: 1, name: 'Monday Lunchtime', day: 1, online: false),
        new MeetingStub(id: 2, name: 'Monday Evening', day: 1, online: true),
        new MeetingStub(id: 3, name: 'Tuesday Evening', day: 2, online: false),
    ]));

    $this->cache = new InMemoryCache();
    $this->repository = new CachingMeetingRepository($this->inner, $this->cache);
});

it('is a meeting repository', function () {
    expect($this->repository)->toBeInstanceOf(MeetingRepository::class);
});

it('does not reach the repository on a second read of the same meeting', function () {
    $first = $this->repository->findById(1);
    $second = $this->repository->findById(1);

    expect($second)->toBe($first)
        ->and($this->inner->findByIdCalls)->toBe(1);
});

it('does not cache an absent meeting, and an impossible id costs nothing', function () {
    expect($this->repository->findById(99))->toBeNull()
        ->and($this->repository->findById(99))->toBeNull()
        ->and($this->inner->findByIdCalls)->toBe(2);

    expect($this->repository->findById(0))->toBeNull()
        ->and($this->inner->findByIdCalls)->toBe(2);
});

it('costs one round trip for a cached listing', function () {
    $this->repository->findAll();
    $this->cache->multiGets = 0;

    expect($this->repository->findAll())->toHaveCount(3)
        ->and($this->inner->findAllCalls)->toBe(1)
        ->and($this->cache->multiGets)->toBe(1);
});

it('caches each day under its own key', function () {
    $monday = $this->repository->findByDay(1);
    $tuesday = $this->repository->findByDay(2);

    $this->repository->findByDay(1);
    $this->repository->findByDay(2);

    expect(meetingIds($monday))->toBe([1, 2])
        ->and(meetingIds($tuesday))->toBe([3])
        ->and($this->inner->findByDayCalls)->toBe(2);
});

it('does not share an entry between the online and in-person listings', function () {
    $online = $this->repository->findOnline();
    $inPerson = $this->repository->findInPerson();

    $this->repository->findOnline();
    $this->repository->findInPerson();

    expect(meetingIds($online))->toBe([2])
        ->and(meetingIds($inPerson))->toBe([1, 3])
        ->and($this->inner->findOnlineCalls)->toBe(1)
        ->and($this->inner->findInPersonCalls)->toBe(1);
});

it('passes a filtered call straight through', function () {
    $this->repository->findByDay(1, ['posts_per_page' => 1]);
    $this->repository->findByDay(1, ['posts_per_page' => 1]);
    $this->repository->findAll(['orderby' => 'title']);
    $this->repository->findAll(['orderby' => 'title']);
    $this->repository->findOnline(['posts_per_page' => 1]);
    $this->repository->findOnline(['posts_per_page' => 1]);
    $this->repository->findInPerson(['posts_per_page' => 1]);
    $this->repository->findInPerson(['posts_per_page' => 1]);

    expect($this->inner->findByDayCalls)->toBe(2)
        ->and($this->inner->findAllCalls)->toBe(2)
        ->and($this->inner->findOnlineCalls)->toBe(2)
        ->and($this->inner->findInPersonCalls)->toBe(2);
});

it('never caches a search', function () {
    $this->repository->search('monday');
    $this->repository->search('monday');

    // The keyword space is unbounded; caching it would mostly store
    // one-off misses.
    expect($this->inner->searchCalls)->toBe(2);
});

it('caches an unfiltered count and not a filtered one', function () {
    $this->repository->count();
    $this->repository->count();
    $this->repository->count(['post_status' => 'draft']);

    expect($this->inner->countCalls)->toBe(2);
});

it('drops every listing and every meeting on a bump', function () {
    $this->repository->findAll();
    $this->repository->findByDay(1);
    $this->repository->findById(1);

    $this->repository->bump();

    $this->repository->findAll();
    $this->repository->findByDay(1);

    expect($this->inner->findAllCalls)->toBe(2)
        ->and($this->inner->findByDayCalls)->toBe(2);
});

it('serves an edited meeting new once the cache is bumped', function () {
    expect($this->repository->findById(1)?->getName())->toBe('Monday Lunchtime');

    // What editing a meeting in the admin does: the row changes without
    // the repository knowing, and PostTypeCacheInvalidator calls bump().
    $this->inner->inner()->replace(new MeetingStub(id: 1, name: 'Monday Noon', day: 1));
    $this->repository->bump();

    expect($this->repository->findById(1)?->getName())->toBe('Monday Noon');
});

it('does not resurrect a stale meeting when the version is evicted', function () {
    $this->repository->findById(1);

    $this->inner->inner()->replace(new MeetingStub(id: 1, name: 'Monday Noon', day: 1));
    $this->repository->bump();

    // Memcached discards entries under memory pressure, the version
    // counter included. Regenerating it from a fixed starting point would
    // put the pre-bump entries back in reach.
    $this->cache->evict('meetings_version', 'unity_meetings');

    expect($this->repository->findById(1)?->getName())->toBe('Monday Noon');
});

it('re-reads meetings evicted from a cached listing in one query', function () {
    $this->repository->findAll();

    $this->inner->findAllCalls = 0;
    evictMeeting($this->cache, 1);
    evictMeeting($this->cache, 3);

    $meetings = $this->repository->findAll();

    expect(meetingIds($meetings))->toBe([1, 2, 3])
        ->and($this->inner->findAllCalls)->toBe(1)
        ->and($this->inner->findByIdCalls)->toBe(0);
});

it('drops a meeting deleted behind the decorator out of a cached listing', function () {
    $this->repository->findAll();

    $this->inner->inner()->remove(2);
    evictMeeting($this->cache, 2);

    expect(meetingIds($this->repository->findAll()))->toBe([1, 3]);
});

it('caches an empty listing and asks the cache for nothing', function () {
    $inner = new CountingMeetingRepository(new MutableMeetingRepository([]));
    $repository = new CachingMeetingRepository($inner, $this->cache);

    expect($repository->findAll())->toBe([]);

    $this->cache->multiGets = 0;

    expect($repository->findAll())->toBe([])
        ->and($inner->findAllCalls)->toBe(1)
        ->and($this->cache->multiGets)->toBe(0);
});

it('expires cached meetings even when nothing bumps them', function () {
    $this->repository->findById(1);

    $expiry = $this->cache->expiries[array_key_last($this->cache->expiries)] ?? 0;

    expect($expiry)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(3600);
});

it('caches each group under its own key', function () {
    $inner = new CountingMeetingRepository(new MutableMeetingRepository([
        new MeetingStub(id: 1, name: 'Monday Lunchtime', day: 1),
        new MeetingStub(id: 2, name: 'Tuesday Evening', day: 2),
    ], [1 => 10, 2 => 11]));
    $repository = new CachingMeetingRepository($inner, $this->cache);

    $ten = $repository->findByGroupId(10);
    $repository->findByGroupId(10);
    $repository->findByGroupId(11);
    $repository->findByGroupId(10, ['posts_per_page' => 1]);

    expect(meetingIds($ten))->toBe([1]);

    // One per group, plus the filtered call, which is never cached.
    expect($inner->findByGroupIdCalls)->toBe(3);
});

it('caches each location under its own key', function () {
    $inner = new CountingMeetingRepository(new MutableMeetingRepository([
        new MeetingStub(id: 1, name: 'Church Hall', location: new LocationStub(id: 20)),
        new MeetingStub(id: 2, name: 'Community Centre', location: new LocationStub(id: 21)),
    ]));
    $repository = new CachingMeetingRepository($inner, $this->cache);

    $twenty = $repository->findByLocationId(20);
    $repository->findByLocationId(20);
    $repository->findByLocationId(21);
    $repository->findByLocationId(20, ['orderby' => 'title']);

    expect(meetingIds($twenty))->toBe([1])
        ->and($inner->findByLocationIdCalls)->toBe(3);
});

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
