<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use Unity\Members\CachingMemberRepository;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;
use Unity\Tests\TestCase;

/**
 * Tests for {@see CachingMemberRepository}.
 *
 * The decorator is only worth having if a second read costs nothing and a
 * write costs correctness nothing, so most of what follows counts calls
 * reaching the inner repository rather than inspecting cache internals.
 */
class CachingMemberRepositoryTest extends TestCase
{
    private CountingMemberRepository $inner;
    private InMemoryCache $cache;
    private CachingMemberRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = new CountingMemberRepository(new InMemoryMemberRepository([
            new MemberStub(id: 1, anonymousName: 'Alice A.', personalEmail: 'alice@example.com', telephoneResponder: true),
            new MemberStub(id: 2, anonymousName: 'Bob B.', personalEmail: 'bob@example.com'),
        ]));

        $this->cache = new InMemoryCache();
        $this->repository = new CachingMemberRepository($this->inner, $this->cache);
    }

    /**
     * @test
     */
    public function it_is_a_member_repository(): void
    {
        $this->assertInstanceOf(MemberRepository::class, $this->repository);
    }

    /**
     * @test
     */
    public function a_second_read_of_the_same_member_does_not_reach_the_repository(): void
    {
        $first = $this->repository->findById(1);
        $second = $this->repository->findById(1);

        $this->assertSame($first, $second);
        $this->assertSame(1, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function an_absent_member_is_not_cached(): void
    {
        $this->assertNull($this->repository->findById(99));
        $this->assertNull($this->repository->findById(99));

        // Caching the miss would mean telling a cached null from an empty
        // slot, which Cache::get() cannot express.
        $this->assertSame(2, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function an_impossible_id_never_reaches_the_repository_or_the_cache(): void
    {
        $this->assertNull($this->repository->findById(0));
        $this->assertSame(0, $this->inner->findByIdCalls);
        $this->assertSame([], $this->cache->reads);
    }

    /**
     * @test
     */
    public function a_member_found_by_email_is_cached_under_both_lookups(): void
    {
        $found = $this->repository->findByEmail('alice@example.com');

        $this->assertInstanceOf(Member::class, $found);
        $this->assertSame(1, $found->getId());

        $this->repository->findByEmail('alice@example.com');
        $this->repository->findById(1);

        $this->assertSame(1, $this->inner->findByEmailCalls);
        $this->assertSame(0, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function a_blank_address_never_reaches_the_repository_or_the_cache(): void
    {
        $this->assertNull($this->repository->findByEmail('   '));
        $this->assertSame(0, $this->inner->findByEmailCalls);
        $this->assertSame([], $this->cache->reads);
    }

    /**
     * @test
     */
    public function an_address_in_another_case_reuses_the_same_entry(): void
    {
        $this->repository->findByEmail('alice@example.com');
        $this->repository->findByEmail('ALICE@Example.com');

        // The real repository matches case-insensitively, so two spellings
        // must not occupy two entries — nor cost two queries.
        $this->assertSame(1, $this->inner->findByEmailCalls);
    }

    /**
     * @test
     */
    public function an_email_address_never_appears_in_a_cache_key(): void
    {
        $this->repository->findByEmail('alice@example.com');

        foreach (array_merge($this->cache->reads, $this->cache->writes) as $key) {
            $this->assertStringNotContainsString('alice@example.com', $key);
        }
    }

    /**
     * @test
     */
    public function a_full_listing_is_served_from_the_cache_the_second_time(): void
    {
        $first = $this->repository->findAll();
        $second = $this->repository->findAll();

        $this->assertCount(2, $second);
        $this->assertSame(
            array_map(static fn (Member $member): int => $member->getId(), $first),
            array_map(static fn (Member $member): int => $member->getId(), $second)
        );
        $this->assertSame(1, $this->inner->findAllCalls);
    }

    /**
     * @test
     */
    public function a_listing_populates_the_entries_the_single_reads_use(): void
    {
        $this->repository->findAll();
        $this->repository->findById(2);

        $this->assertSame(0, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function a_cached_listing_costs_one_round_trip_rather_than_one_per_member(): void
    {
        $this->repository->findAll();
        $this->cache->multiGets = 0;

        $this->repository->findAll();

        // The point of the multi-get: a few hundred members is one round trip
        // to Memcached, not a few hundred.
        $this->assertSame(1, $this->cache->multiGets);
    }

    /**
     * @test
     */
    public function members_evicted_from_a_cached_listing_are_re_read_in_one_query(): void
    {
        $listing = $this->repository->findAll();
        $this->assertCount(2, $listing);

        $this->inner->findAllCalls = 0;
        foreach ([1, 2] as $id) {
            $this->evictMember($id);
        }

        $refetched = $this->repository->findAll();

        $this->assertSame([1, 2], array_map(static fn (Member $member): int => $member->getId(), $refetched));
        $this->assertSame(1, $this->inner->findAllCalls);
        $this->assertSame(0, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function a_listing_keeps_its_order_when_only_some_members_are_evicted(): void
    {
        $this->repository->findAll();
        $this->evictMember(1);

        $members = $this->repository->findAll();

        $this->assertSame([1, 2], array_map(static fn (Member $member): int => $member->getId(), $members));
    }

    /**
     * @test
     */
    public function a_member_deleted_behind_the_decorator_drops_out_of_a_cached_listing(): void
    {
        $this->repository->findAll();

        $this->inner->inner()->delete(2);
        $this->evictMember(2);

        $members = $this->repository->findAll();

        // The list is stale until the next bump, but serving a member who no
        // longer exists would be worse than serving a short list.
        $this->assertSame([1], array_map(static fn (Member $member): int => $member->getId(), $members));
    }

    /**
     * @test
     */
    public function an_empty_listing_is_cached_and_asks_the_cache_for_nothing(): void
    {
        $inner = new CountingMemberRepository(new InMemoryMemberRepository([]));
        $repository = new CachingMemberRepository($inner, $this->cache);

        $this->assertSame([], $repository->findAll());

        $this->cache->multiGets = 0;

        $this->assertSame([], $repository->findAll());
        $this->assertSame(1, $inner->findAllCalls);
        $this->assertSame(0, $this->cache->multiGets);
    }

    /**
     * @test
     */
    public function a_filtered_listing_is_passed_straight_through(): void
    {
        $this->repository->findAll(['post__in' => [2]]);
        $this->repository->findAll(['post__in' => [2]]);

        // A key built from arbitrary get_posts() arguments would be a key
        // nobody can reason about; these reads are not cached at all.
        $this->assertSame(2, $this->inner->findAllCalls);
    }

    /**
     * @test
     */
    public function telephone_responders_are_cached_separately_from_the_full_listing(): void
    {
        $responders = $this->repository->findTelephoneResponders();
        $this->repository->findTelephoneResponders();

        $this->assertCount(1, $responders);
        $this->assertSame(1, $responders[0]->getId());
        $this->assertSame(1, $this->inner->findResponderCalls);
        $this->assertSame(0, $this->inner->findAllCalls);
    }

    /**
     * @test
     */
    public function an_unfiltered_count_is_cached_and_a_filtered_one_is_not(): void
    {
        $this->repository->count();
        $this->repository->count();
        $this->repository->count(['post_status' => 'draft']);

        $this->assertSame(2, $this->inner->countCalls);
    }

    /**
     * @test
     */
    public function saving_a_member_invalidates_what_was_cached(): void
    {
        $this->repository->findById(1);
        $this->repository->save(new MemberStub(id: 1, anonymousName: 'Alice C.'));

        $member = $this->repository->findById(1);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame('Alice C.', $member->getAnonymousName());
        $this->assertSame(2, $this->inner->findByIdCalls);
    }

    /**
     * @test
     */
    public function deleting_a_member_invalidates_the_listing(): void
    {
        $this->repository->findAll();
        $this->repository->delete(2);

        $this->assertCount(1, $this->repository->findAll());
    }

    /**
     * @test
     */
    public function creating_and_updating_invalidate_too(): void
    {
        $this->repository->findAll();
        $this->repository->create('Carol C.');
        $this->assertCount(3, $this->repository->findAll());

        $this->repository->update(new MemberStub(id: 2, anonymousName: 'Bob D.'));
        $members = $this->repository->findAll();

        $this->assertSame('Bob D.', $members[1]->getAnonymousName());
    }

    /**
     * @test
     */
    public function bump_invalidates_a_write_made_behind_the_decorator(): void
    {
        $this->repository->findById(1);

        // What Reconcile, the ACF admin screen and Scrutiny's pruner all do:
        // change the member without going through this repository at all.
        $this->inner->inner()->save(new MemberStub(id: 1, anonymousName: 'Alice E.'));
        $this->repository->bump();

        $member = $this->repository->findById(1);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame('Alice E.', $member->getAnonymousName());
    }

    /**
     * @test
     */
    public function an_evicted_version_does_not_resurrect_stale_members(): void
    {
        $this->repository->findById(1);

        $this->inner->inner()->save(new MemberStub(id: 1, anonymousName: 'Alice F.'));
        $this->repository->bump();

        // Memcached discards entries when it runs short of memory, and the
        // version counter is as evictable as anything else. Regenerating it
        // from a fixed starting point would bring the pre-bump entries back
        // into reach — the stale member returning because the cache filled up.
        $this->cache->evict('members_version', 'unity_members');

        $member = $this->repository->findById(1);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame('Alice F.', $member->getAnonymousName());
    }

    /**
     * @test
     */
    public function cached_members_expire_even_when_nothing_clears_them(): void
    {
        $this->repository->findById(1);

        $expiry = $this->cache->expiries[array_key_last($this->cache->expiries)] ?? 0;

        // Members carry personal data. An entry that never expires is one a
        // GDPR erasure can miss for as long as the cache holds it.
        $this->assertGreaterThan(0, $expiry);
        $this->assertLessThanOrEqual(86400, $expiry);
    }

    /**
     * Drop one member's entry the way a cache short of memory would, leaving
     * the cached id list — and the version it was written under — intact.
     */
    private function evictMember(int $id): void
    {
        $version = $this->cache->get('members_version', 'unity_members');

        $this->cache->evict('member_' . $id . ':' . (is_string($version) ? $version : ''), 'unity_members');
    }
}

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
