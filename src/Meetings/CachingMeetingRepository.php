<?php

declare(strict_types=1);

namespace Unity\Meetings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\CacheBumper;
use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Interfaces\MeetingRepository;

/**
 * Caching decorator for a MeetingRepository.
 *
 * The same shape as {@see \Unity\Members\CachingMemberRepository}, and for the
 * same reason: building a meeting walks ACF field by field, the reads are
 * constant — every public meetings page — and the writes are occasional.
 *
 * MeetingRepository is read-only, so nothing here can invalidate itself. It is
 * correct only alongside {@see \Unity\Core\PostTypeCacheInvalidator} hooked to
 * the meeting post type, and wrong without it: a meeting edited in the admin
 * would keep serving its old day and time until the entry expired. On a
 * helpline site that is a caller sent to a meeting that is no longer there,
 * which is why the version prefix is not optional decoration.
 *
 * Unfiltered calls are cached; anything carrying $args is passed straight
 * through, because a key built from arbitrary get_posts() arguments is a key
 * nobody can reason about. search() is never cached: the keyword space is
 * unbounded, and a cache of one-off searches mostly stores misses.
 */
class CachingMeetingRepository implements MeetingRepository, CacheBumper
{
    private const GROUP = 'unity_meetings';
    private const VERSION_KEY = 'meetings_version';

    /**
     * An hour, as the repository this replaces used.
     *
     * The version prefix is what keeps meetings current; this only bounds how
     * long a stale entry could survive a missed bump — a backstop, not the
     * mechanism.
     */
    private const TTL = 3600;

    /** Versions minted in this request, so that two in one millisecond differ. */
    private static int $sequence = 0;

    public function __construct(
        private readonly MeetingRepository $inner,
        private readonly Cache $cache,
    ) {
    }

    public function findById(int $id): ?Meeting
    {
        if ($id <= 0) {
            return null;
        }

        $cached = $this->cache->get($this->key('meeting_' . $id), self::GROUP);

        if ($cached instanceof Meeting) {
            return $cached;
        }

        $meeting = $this->inner->findById($id);

        // Misses are not cached: Cache::get() reports "not found" as false, so
        // a cached null could not be told from an empty slot.
        if ($meeting !== null) {
            $this->store($meeting);
        }

        return $meeting;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findAll(array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findAll($args);
        }

        return $this->cachedList('all_ids', fn (): array => $this->inner->findAll());
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByDay(int $day, array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findByDay($day, $args);
        }

        return $this->cachedList('day_' . $day, fn (): array => $this->inner->findByDay($day));
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findOnline(array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findOnline($args);
        }

        return $this->cachedList('online', fn (): array => $this->inner->findOnline());
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findInPerson(array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findInPerson($args);
        }

        return $this->cachedList('in_person', fn (): array => $this->inner->findInPerson());
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByGroupId(int $groupId, array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findByGroupId($groupId, $args);
        }

        return $this->cachedList('group_' . $groupId, fn (): array => $this->inner->findByGroupId($groupId));
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByLocationId(int $locationId, array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findByLocationId($locationId, $args);
        }

        return $this->cachedList('location_' . $locationId, fn (): array => $this->inner->findByLocationId($locationId));
    }

    /**
     * {@inheritdoc}
     *
     * Never cached — see the class docblock.
     *
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function search(string $keyword, array $args = []): array
    {
        return $this->inner->search($keyword, $args);
    }

    /** @param array<string, mixed> $args */
    public function count(array $args = []): int
    {
        if ($args !== []) {
            return $this->inner->count($args);
        }

        $key = $this->key('count');
        $count = $this->cache->get($key, self::GROUP);

        if (is_int($count)) {
            return $count;
        }

        $count = $this->inner->count();
        $this->cache->set($key, $count, self::GROUP, self::TTL);

        return $count;
    }

    /**
     * Invalidate every meeting this repository has cached.
     *
     * The only way in: the contract has no writes, so every change to a
     * meeting arrives through {@see \Unity\Core\PostTypeCacheInvalidator}.
     */
    public function bump(): void
    {
        $this->cache->set(self::VERSION_KEY, $this->newVersion(), self::GROUP);
    }

    /**
     * A cached list of ids, hydrated in one multi-get.
     *
     * Only reached for the unfiltered call: every finder above delegates
     * straight to the inner repository when it was given $args.
     *
     * @param callable(): array<int, Meeting> $load
     * @return array<int, Meeting>
     */
    private function cachedList(string $name, callable $load): array
    {
        $key = $this->key($name);
        $ids = $this->cache->get($key, self::GROUP);

        if (is_array($ids)) {
            /** @var array<int, int> $ids */
            $ids = array_values(array_filter($ids, 'is_int'));

            return $this->meetingsFor($ids);
        }

        $meetings = $load();
        $ids = [];

        foreach ($meetings as $meeting) {
            $ids[] = $meeting->getId();
            $this->store($meeting);
        }

        $this->cache->set($key, $ids, self::GROUP, self::TTL);

        return $meetings;
    }

    /**
     * The meetings for a known set of ids, in the order given.
     *
     * Entries the multi-get does not find are re-read in one query through
     * post__in rather than one findById() each, so a cache that has evicted
     * half a timetable costs one query rather than a hundred.
     *
     * @param array<int, int> $ids
     * @return array<int, Meeting>
     */
    private function meetingsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $keys = [];

        foreach ($ids as $id) {
            $keys[$this->key('meeting_' . $id)] = $id;
        }

        $cached = $this->cache->getMultiple(array_keys($keys), self::GROUP);

        $found = [];
        $missing = [];

        foreach ($keys as $cacheKey => $id) {
            $entry = $cached[$cacheKey] ?? false;

            if ($entry instanceof Meeting) {
                $found[$id] = $entry;
                continue;
            }

            $missing[] = $id;
        }

        if ($missing !== []) {
            foreach ($this->inner->findAll(['post__in' => $missing]) as $meeting) {
                $found[$meeting->getId()] = $meeting;
                $this->store($meeting);
            }
        }

        $meetings = [];

        foreach ($ids as $id) {
            // A meeting deleted since the list was cached simply drops out;
            // the next change bumps the version and rebuilds the list anyway.
            if (isset($found[$id])) {
                $meetings[] = $found[$id];
            }
        }

        return $meetings;
    }

    private function store(Meeting $meeting): void
    {
        $this->cache->set($this->key('meeting_' . $meeting->getId()), $meeting, self::GROUP, self::TTL);
    }

    private function key(string $name): string
    {
        return $name . ':' . $this->version();
    }

    private function version(): string
    {
        $version = $this->cache->get(self::VERSION_KEY, self::GROUP);

        if (is_string($version) && $version !== '') {
            return $version;
        }

        // An evicted version has to regenerate to a value never used before.
        // Resetting to a fixed starting point would bring pre-bump entries
        // back into reach — a stale meeting returning because the cache ran
        // short of memory.
        $version = $this->newVersion();
        $this->cache->set(self::VERSION_KEY, $version, self::GROUP);

        return $version;
    }

    private function newVersion(): string
    {
        return dechex((int) (microtime(true) * 1000))
            . dechex(random_int(0, 0xFFFF))
            . dechex(++self::$sequence);
    }
}
