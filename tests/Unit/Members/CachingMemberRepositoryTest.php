<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use Unity\Members\CachingMemberRepository;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;

/*
 * Tests for {@see CachingMemberRepository}.
 *
 * The decorator is only worth having if a second read costs nothing and a
 * write costs correctness nothing, so most of what follows counts calls
 * reaching the inner repository rather than inspecting cache internals.
 */

/**
 * Drop one member's entry the way a cache short of memory would, leaving
 * the cached id list — and the version it was written under — intact.
 */
function evictMember(InMemoryCache $cache, int $id): void
{
    $version = $cache->get('members_version', 'unity_members');

    $cache->evict('member_' . $id . ':' . (is_string($version) ? $version : ''), 'unity_members');
}

/**
 * @param array<int, Member> $members
 * @return array<int, int>
 */
function memberIds(array $members): array
{
    return array_map(static fn (Member $member): int => $member->getId(), $members);
}

beforeEach(function () {
    $this->inner = new CountingMemberRepository(new InMemoryMemberRepository([
        new MemberStub(id: 1, anonymousName: 'Alice A.', personalEmail: 'alice@example.com', telephoneResponder: true),
        new MemberStub(id: 2, anonymousName: 'Bob B.', personalEmail: 'bob@example.com'),
    ]));

    $this->cache = new InMemoryCache();
    $this->repository = new CachingMemberRepository($this->inner, $this->cache);
});

it('is a member repository', function () {
    expect($this->repository)->toBeInstanceOf(MemberRepository::class);
});

it('does not reach the repository on a second read of the same member', function () {
    $first = $this->repository->findById(1);
    $second = $this->repository->findById(1);

    expect($second)->toBe($first)
        ->and($this->inner->findByIdCalls)->toBe(1);
});

it('does not cache an absent member', function () {
    expect($this->repository->findById(99))->toBeNull()
        ->and($this->repository->findById(99))->toBeNull();

    // Caching the miss would mean telling a cached null from an empty
    // slot, which Cache::get() cannot express.
    expect($this->inner->findByIdCalls)->toBe(2);
});

it('never takes an impossible id to the repository or the cache', function () {
    expect($this->repository->findById(0))->toBeNull()
        ->and($this->inner->findByIdCalls)->toBe(0)
        ->and($this->cache->reads)->toBe([]);
});

it('caches a member found by email under both lookups', function () {
    $found = $this->repository->findByEmail('alice@example.com');

    expect($found)->toBeInstanceOf(Member::class)
        ->and($found->getId())->toBe(1);

    $this->repository->findByEmail('alice@example.com');
    $this->repository->findById(1);

    expect($this->inner->findByEmailCalls)->toBe(1)
        ->and($this->inner->findByIdCalls)->toBe(0);
});

it('never takes a blank address to the repository or the cache', function () {
    expect($this->repository->findByEmail('   '))->toBeNull()
        ->and($this->inner->findByEmailCalls)->toBe(0)
        ->and($this->cache->reads)->toBe([]);
});

it('reuses the same entry for an address in another case', function () {
    $this->repository->findByEmail('alice@example.com');
    $this->repository->findByEmail('ALICE@Example.com');

    // The real repository matches case-insensitively, so two spellings
    // must not occupy two entries — nor cost two queries.
    expect($this->inner->findByEmailCalls)->toBe(1);
});

it('never puts an email address in a cache key', function () {
    $this->repository->findByEmail('alice@example.com');

    foreach (array_merge($this->cache->reads, $this->cache->writes) as $key) {
        expect($key)->not->toContain('alice@example.com');
    }
});

it('serves a full listing from the cache the second time', function () {
    $first = $this->repository->findAll();
    $second = $this->repository->findAll();

    expect($second)->toHaveCount(2)
        ->and(memberIds($second))->toBe(memberIds($first))
        ->and($this->inner->findAllCalls)->toBe(1);
});

it('populates the entries the single reads use from a listing', function () {
    $this->repository->findAll();
    $this->repository->findById(2);

    expect($this->inner->findByIdCalls)->toBe(0);
});

it('costs one round trip for a cached listing rather than one per member', function () {
    $this->repository->findAll();
    $this->cache->multiGets = 0;

    $this->repository->findAll();

    // The point of the multi-get: a few hundred members is one round trip
    // to Memcached, not a few hundred.
    expect($this->cache->multiGets)->toBe(1);
});

it('re-reads members evicted from a cached listing in one query', function () {
    $listing = $this->repository->findAll();
    expect($listing)->toHaveCount(2);

    $this->inner->findAllCalls = 0;
    foreach ([1, 2] as $id) {
        evictMember($this->cache, $id);
    }

    $refetched = $this->repository->findAll();

    expect(memberIds($refetched))->toBe([1, 2])
        ->and($this->inner->findAllCalls)->toBe(1)
        ->and($this->inner->findByIdCalls)->toBe(0);
});

it('keeps a listing in order when only some members are evicted', function () {
    $this->repository->findAll();
    evictMember($this->cache, 1);

    $members = $this->repository->findAll();

    expect(memberIds($members))->toBe([1, 2]);
});

it('drops a member deleted behind the decorator out of a cached listing', function () {
    $this->repository->findAll();

    $this->inner->inner()->delete(2);
    evictMember($this->cache, 2);

    $members = $this->repository->findAll();

    // The list is stale until the next bump, but serving a member who no
    // longer exists would be worse than serving a short list.
    expect(memberIds($members))->toBe([1]);
});

it('caches an empty listing and asks the cache for nothing', function () {
    $inner = new CountingMemberRepository(new InMemoryMemberRepository([]));
    $repository = new CachingMemberRepository($inner, $this->cache);

    expect($repository->findAll())->toBe([]);

    $this->cache->multiGets = 0;

    expect($repository->findAll())->toBe([])
        ->and($inner->findAllCalls)->toBe(1)
        ->and($this->cache->multiGets)->toBe(0);
});

it('passes a filtered listing straight through', function () {
    $this->repository->findAll(['post__in' => [2]]);
    $this->repository->findAll(['post__in' => [2]]);

    // A key built from arbitrary get_posts() arguments would be a key
    // nobody can reason about; these reads are not cached at all.
    expect($this->inner->findAllCalls)->toBe(2);
});

it('caches telephone responders separately from the full listing', function () {
    $responders = $this->repository->findTelephoneResponders();
    $this->repository->findTelephoneResponders();

    expect($responders)->toHaveCount(1)
        ->and($responders[0]->getId())->toBe(1)
        ->and($this->inner->findResponderCalls)->toBe(1)
        ->and($this->inner->findAllCalls)->toBe(0);
});

it('caches an unfiltered count and not a filtered one', function () {
    $this->repository->count();
    $this->repository->count();
    $this->repository->count(['post_status' => 'draft']);

    expect($this->inner->countCalls)->toBe(2);
});

it('invalidates what was cached when a member is saved', function () {
    $this->repository->findById(1);
    $this->repository->save(new MemberStub(id: 1, anonymousName: 'Alice C.'));

    $member = $this->repository->findById(1);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->getAnonymousName())->toBe('Alice C.')
        ->and($this->inner->findByIdCalls)->toBe(2);
});

it('invalidates the listing when a member is deleted', function () {
    $this->repository->findAll();
    $this->repository->delete(2);

    expect($this->repository->findAll())->toHaveCount(1);
});

it('invalidates on create and update too', function () {
    $this->repository->findAll();
    $this->repository->create('Carol C.');
    expect($this->repository->findAll())->toHaveCount(3);

    $this->repository->update(new MemberStub(id: 2, anonymousName: 'Bob D.'));
    $members = $this->repository->findAll();

    expect($members[1]->getAnonymousName())->toBe('Bob D.');
});

it('invalidates a write made behind the decorator on bump', function () {
    $this->repository->findById(1);

    // What Reconcile, the ACF admin screen and Scrutiny's pruner all do:
    // change the member without going through this repository at all.
    $this->inner->inner()->save(new MemberStub(id: 1, anonymousName: 'Alice E.'));
    $this->repository->bump();

    $member = $this->repository->findById(1);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->getAnonymousName())->toBe('Alice E.');
});

it('does not resurrect stale members when the version is evicted', function () {
    $this->repository->findById(1);

    $this->inner->inner()->save(new MemberStub(id: 1, anonymousName: 'Alice F.'));
    $this->repository->bump();

    // Memcached discards entries when it runs short of memory, and the
    // version counter is as evictable as anything else. Regenerating it
    // from a fixed starting point would bring the pre-bump entries back
    // into reach — the stale member returning because the cache filled up.
    $this->cache->evict('members_version', 'unity_members');

    $member = $this->repository->findById(1);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->getAnonymousName())->toBe('Alice F.');
});

it('expires cached members even when nothing clears them', function () {
    $this->repository->findById(1);

    $expiry = $this->cache->expiries[array_key_last($this->cache->expiries)] ?? 0;

    // Members carry personal data. An entry that never expires is one a
    // GDPR erasure can miss for as long as the cache holds it.
    expect($expiry)->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(86400);
});

/**
 * A MemberRepository that counts the reads reaching it.
 *
 * Wraps InMemoryMemberRepository — which is final, and rightly so — rather
 * than subclassing it, so the behaviour under the counters is the shared
 * double's rather than a second approximation of the real repository.
 */
final class CountingMemberRepository implements MemberRepository
{
    public int $findByIdCalls = 0;
    public int $findByEmailCalls = 0;
    public int $findAllCalls = 0;
    public int $findResponderCalls = 0;
    public int $countCalls = 0;

    public function __construct(private InMemoryMemberRepository $inner)
    {
    }

    /** The double underneath, for a write that bypasses the decorator. */
    public function inner(): InMemoryMemberRepository
    {
        return $this->inner;
    }

    public function findById(int $id): ?Member
    {
        $this->findByIdCalls++;

        return $this->inner->findById($id);
    }

    public function findByEmail(string $email): ?Member
    {
        $this->findByEmailCalls++;

        return $this->inner->findByEmail($email);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Member>
     */
    public function findAll(array $args = []): array
    {
        $this->findAllCalls++;

        return $this->inner->findAll($args);
    }

    /** @return array<int, Member> */
    public function findTelephoneResponders(): array
    {
        $this->findResponderCalls++;

        return $this->inner->findTelephoneResponders();
    }

    /** @param array<string, mixed> $args */
    public function count(array $args = []): int
    {
        $this->countCalls++;

        return $this->inner->count($args);
    }

    public function create(string $anonymousName): int
    {
        return $this->inner->create($anonymousName);
    }

    public function save(Member $member): bool
    {
        return $this->inner->save($member);
    }

    public function delete(int $id): bool
    {
        return $this->inner->delete($id);
    }

    public function update(Member $member): bool
    {
        return $this->inner->update($member);
    }
}
