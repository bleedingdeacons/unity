<?php

declare(strict_types=1);

namespace Unity\Members;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\Interfaces\Cache;
use Unity\Core\Interfaces\CacheBumper;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;

/**
 * Caching decorator for a MemberRepository.
 *
 * Members are read constantly and written rarely, and building one costs
 * around twenty-five get_field() calls through ACF. This holds the finished
 * objects so that work happens once per member per write rather than once per
 * request. It only matters with a persistent object cache behind
 * {@see Cache} — without one the entries die with the request, and this
 * degrades to the inner repository plus a little bookkeeping.
 *
 * Invalidation is a version counter rather than per-key deletes: a write
 * bumps the version and every key moves with it. That suits sporadic writes,
 * and it avoids needing to clear a group — Memcached drop-ins generally do
 * not implement wp_cache_flush_group(), and wp_cache_flush() would clear the
 * whole shared cache, including every other plugin's entries and, on a host
 * where two installs share one instance, the other site's.
 *
 * Writes made outside this class — the ACF admin screen, Reconcile, Scrutiny's
 * pruner — never reach {@see bump()}, so this is only correct alongside
 * {@see MemberCacheInvalidator}, which hooks WordPress's own post and meta
 * actions.
 */
class CachingMemberRepository implements MemberRepository, CacheBumper
{
    private const GROUP = 'unity_members';
    private const VERSION_KEY = 'members_version';

    /**
     * Members carry personal data, so entries expire even when a clear is
     * missed. Twelve hours is long enough to be worth having and short enough
     * that a member erased under GDPR cannot linger in memory indefinitely.
     */
    private const TTL = 43200;

    /** Versions minted in this request, so that two in one millisecond differ. */
    private static int $sequence = 0;

    public function __construct(
        private readonly MemberRepository $inner,
        private readonly Cache $cache,
    ) {
    }

    public function findById(int $id): ?Member
    {
        if ($id <= 0) {
            return null;
        }

        $cached = $this->cache->get($this->key('member_' . $id), self::GROUP);

        if ($cached instanceof Member) {
            return $cached;
        }

        $member = $this->inner->findById($id);

        // Misses are not cached. Cache::get() reports "not found" as false, so
        // a cached null could not be told from an empty slot without boxing
        // every entry, and a member who does not exist is not the read worth
        // optimising.
        if ($member !== null) {
            $this->store($member);
        }

        return $member;
    }

    public function findByEmail(string $email): ?Member
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        // The key holds a hash of the address rather than the address itself:
        // keys reach logs and cache-inspection tools, and a member's email is
        // personal data wherever it lands. Lower-cased first because the real
        // repository matches case-insensitively, so two spellings of one
        // address must not occupy two entries.
        $key = $this->key('email_' . md5(strtolower($email)));
        $id = $this->cache->get($key, self::GROUP);

        if (is_int($id)) {
            return $this->findById($id);
        }

        $member = $this->inner->findByEmail($email);

        if ($member !== null) {
            $this->cache->set($key, $member->getId(), self::GROUP, self::TTL);
            $this->store($member);
        }

        return $member;
    }

    /**
     * {@inheritdoc}
     *
     * Only the unfiltered call is cached. Callers pass arbitrary get_posts()
     * arguments — post__in, meta queries, orderby — and a key built from those
     * would either be wrong or be a hash of a structure nobody can reason
     * about. The unfiltered list is the one the Link directory and the admin
     * screens actually ask for, and it is the expensive one.
     *
     * @param array<string, mixed> $args
     * @return array<int, Member>
     */
    public function findAll(array $args = []): array
    {
        if ($args !== []) {
            return $this->inner->findAll($args);
        }

        return $this->hydrate('all_ids', fn (): array => $this->inner->findAll());
    }

    /** @return array<int, Member> */
    public function findTelephoneResponders(): array
    {
        return $this->hydrate('responder_ids', fn (): array => $this->inner->findTelephoneResponders());
    }

    /**
     * {@inheritdoc}
     *
     * Cached for the unfiltered call only, for the same reason as
     * {@see findAll()}.
     *
     * @param array<string, mixed> $args
     */
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

    public function create(string $anonymousName): int
    {
        $id = $this->inner->create($anonymousName);
        $this->bump();

        return $id;
    }

    public function save(Member $member): bool
    {
        $saved = $this->inner->save($member);
        $this->bump();

        return $saved;
    }

    public function update(Member $member): bool
    {
        $updated = $this->inner->update($member);
        $this->bump();

        return $updated;
    }

    public function delete(int $id): bool
    {
        $deleted = $this->inner->delete($id);
        $this->bump();

        return $deleted;
    }

    /**
     * Invalidate everything this repository has cached.
     *
     * Public because the write that matters usually happens elsewhere; see
     * {@see MemberCacheInvalidator}. Bumping after a write that failed is
     * harmless — it costs one rebuild — so no path here checks the inner
     * result first, and a bulk import that bumps once per field is paying for
     * a rebuild it was going to need anyway.
     */
    public function bump(): void
    {
        $this->cache->set(self::VERSION_KEY, $this->newVersion(), self::GROUP);
    }

    /**
     * A cached list of ids, with the members fetched in one multi-get so that
     * the single and list reads share one set of entries.
     *
     * The entries a multi-get does not find are re-read in one query through
     * post__in rather than one findById() each: a cache that has evicted half
     * a directory should cost one query, not two hundred.
     *
     * @param callable(): array<int, Member> $load
     * @return array<int, Member>
     */
    private function hydrate(string $name, callable $load): array
    {
        $key = $this->key($name);
        $ids = $this->cache->get($key, self::GROUP);

        if (is_array($ids)) {
            /** @var array<int, int> $ids */
            $ids = array_values(array_filter($ids, 'is_int'));

            return $this->membersFor($ids);
        }

        $members = $load();
        $ids = [];

        foreach ($members as $member) {
            $ids[] = $member->getId();
            $this->store($member);
        }

        $this->cache->set($key, $ids, self::GROUP, self::TTL);

        return $members;
    }

    /**
     * The members for a known set of ids, in the order given.
     *
     * @param array<int, int> $ids
     * @return array<int, Member>
     */
    private function membersFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $keys = [];

        foreach ($ids as $id) {
            $keys[$this->key('member_' . $id)] = $id;
        }

        $cached = $this->cache->getMultiple(array_keys($keys), self::GROUP);

        $found = [];
        $missing = [];

        foreach ($keys as $cacheKey => $id) {
            $entry = $cached[$cacheKey] ?? false;

            if ($entry instanceof Member) {
                $found[$id] = $entry;
                continue;
            }

            $missing[] = $id;
        }

        if ($missing !== []) {
            foreach ($this->inner->findAll(['post__in' => $missing]) as $member) {
                $found[$member->getId()] = $member;
                $this->store($member);
            }
        }

        $members = [];

        foreach ($ids as $id) {
            // A member deleted since the list was cached simply drops out;
            // the next write bumps the version and rebuilds the list anyway.
            if (isset($found[$id])) {
                $members[] = $found[$id];
            }
        }

        return $members;
    }

    private function store(Member $member): void
    {
        $this->cache->set($this->key('member_' . $member->getId()), $member, self::GROUP, self::TTL);
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

        // The version can be evicted like anything else, so a miss has to
        // produce a value never used before rather than a fixed starting
        // point. Resetting to "1" would resurrect entries written before the
        // last bump — stale members brought back by the cache running short of
        // memory.
        $version = $this->newVersion();
        $this->cache->set(self::VERSION_KEY, $version, self::GROUP);

        return $version;
    }

    /**
     * A value never used before.
     *
     * The clock alone is not enough: two writes inside one millisecond would
     * produce the same version, and the second would leave the first's entries
     * live. The counter settles that within a request and the random half
     * settles it between them.
     */
    private function newVersion(): string
    {
        return dechex((int) (microtime(true) * 1000))
            . dechex(random_int(0, 0xFFFF))
            . dechex(++self::$sequence);
    }
}
