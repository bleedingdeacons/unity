<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use PHPUnit\Framework\Attributes\Test;
use function Brain\Monkey\Functions\when;
use Unity\Members\CachingMemberRepository;
use Unity\Members\MemberCacheInvalidator;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;
use Unity\Tests\TestCase;

/**
 * Tests for {@see MemberCacheInvalidator}.
 *
 * The decorator is only safe alongside this: nearly every real member write
 * happens outside the repository, so these hooks are what stop a cached member
 * outliving the edit that changed them.
 */
class MemberCacheInvalidatorTest extends TestCase
{
    private const POST_TYPE = 'unity_member';

    private InMemoryCache $cache;
    private CachingMemberRepository $repository;
    private MemberCacheInvalidator $invalidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new InMemoryCache();
        $this->repository = new CachingMemberRepository(
            new InMemoryMemberRepository([new MemberStub(id: 1, anonymousName: 'Alice A.')]),
            $this->cache
        );
        $this->invalidator = new MemberCacheInvalidator($this->repository, self::POST_TYPE);
    }

    #[Test]
    public function it_hooks_the_post_actions(): void
    {
        $this->invalidator->register();

        $this->assertActionAdded('save_post_' . self::POST_TYPE);
        $this->assertActionAdded('trashed_post');
        $this->assertActionAdded('untrashed_post');
        $this->assertActionAdded('before_delete_post');
    }

    #[Test]
    public function it_hooks_the_meta_actions(): void
    {
        $this->invalidator->register();

        // ACF writes field by field, so these are what clear the cache before
        // the change tracker re-reads the member and diffs it.
        $this->assertActionAdded('added_post_meta');
        $this->assertActionAdded('updated_post_meta');
        $this->assertActionAdded('deleted_post_meta');
    }

    #[Test]
    public function a_meta_write_on_a_member_invalidates_the_cache(): void
    {
        when('get_post_type')->justReturn(self::POST_TYPE);

        $version = $this->versionAfterAReadOfMemberOne();

        $this->invalidator->onMetaChanged(101, 1);

        $this->assertNotSame($version, $this->version());
    }

    #[Test]
    public function a_meta_write_on_anything_else_leaves_the_cache_alone(): void
    {
        when('get_post_type')->justReturn('page');

        $version = $this->versionAfterAReadOfMemberOne();

        $this->invalidator->onMetaChanged(101, 55);

        // Every post save on the site reaches these hooks. Bumping on all of
        // them would empty the member cache several times a minute on a busy
        // admin session, for edits that have nothing to do with members.
        $this->assertSame($version, $this->version());
    }

    #[Test]
    public function a_deleted_meta_row_passes_an_array_of_ids_and_is_handled_anyway(): void
    {
        when('get_post_type')->justReturn(self::POST_TYPE);

        $version = $this->versionAfterAReadOfMemberOne();

        // deleted_post_meta hands over an array of meta ids where the other
        // two pass one — the signatures genuinely differ, and neither value
        // matters here.
        $this->invalidator->onMetaChanged([101, 102], 1);

        $this->assertNotSame($version, $this->version());
    }

    #[Test]
    public function a_member_post_save_invalidates_the_cache(): void
    {
        when('get_post_type')->justReturn(self::POST_TYPE);

        $version = $this->versionAfterAReadOfMemberOne();

        $this->invalidator->onPostChanged(1);

        $this->assertNotSame($version, $this->version());
    }

    private function versionAfterAReadOfMemberOne(): string
    {
        $this->repository->findById(1);

        return $this->version();
    }

    private function version(): string
    {
        $version = $this->cache->get('members_version', 'unity_members');

        return is_string($version) ? $version : '';
    }
}
