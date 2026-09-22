<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use Brain\Monkey\Functions;
use Unity\Members\CachingMemberRepository;
use Unity\Members\MemberCacheInvalidator;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;

/*
 * Tests for {@see MemberCacheInvalidator}.
 *
 * The decorator is only safe alongside this: nearly every real member write
 * happens outside the repository, so these hooks are what stop a cached member
 * outliving the edit that changed them.
 */

const MEMBER_POST_TYPE = 'unity_member';

function memberCacheVersion(InMemoryCache $cache): string
{
    $version = $cache->get('members_version', 'unity_members');

    return is_string($version) ? $version : '';
}

beforeEach(function () {
    $this->cache = new InMemoryCache();
    $this->repository = new CachingMemberRepository(
        new InMemoryMemberRepository([new MemberStub(id: 1, anonymousName: 'Alice A.')]),
        $this->cache
    );
    $this->invalidator = new MemberCacheInvalidator($this->repository, MEMBER_POST_TYPE);

    $this->versionAfterAReadOfMemberOne = function (): string {
        $this->repository->findById(1);

        return memberCacheVersion($this->cache);
    };
});

it('hooks the post actions', function () {
    $this->invalidator->register();

    $this->assertActionAdded('save_post_' . MEMBER_POST_TYPE);
    $this->assertActionAdded('trashed_post');
    $this->assertActionAdded('untrashed_post');
    $this->assertActionAdded('before_delete_post');
});

it('hooks the meta actions', function () {
    $this->invalidator->register();

    // ACF writes field by field, so these are what clear the cache before
    // the change tracker re-reads the member and diffs it.
    $this->assertActionAdded('added_post_meta');
    $this->assertActionAdded('updated_post_meta');
    $this->assertActionAdded('deleted_post_meta');
});

it('invalidates the cache on a meta write to a member', function () {
    Functions\when('get_post_type')->justReturn(MEMBER_POST_TYPE);

    $version = ($this->versionAfterAReadOfMemberOne)();

    $this->invalidator->onMetaChanged(101, 1);

    expect(memberCacheVersion($this->cache))->not->toBe($version);
});

it('leaves the cache alone on a meta write to anything else', function () {
    Functions\when('get_post_type')->justReturn('page');

    $version = ($this->versionAfterAReadOfMemberOne)();

    $this->invalidator->onMetaChanged(101, 55);

    // Every post save on the site reaches these hooks. Bumping on all of
    // them would empty the member cache several times a minute on a busy
    // admin session, for edits that have nothing to do with members.
    expect(memberCacheVersion($this->cache))->toBe($version);
});

it('handles a deleted meta row passing an array of ids anyway', function () {
    Functions\when('get_post_type')->justReturn(MEMBER_POST_TYPE);

    $version = ($this->versionAfterAReadOfMemberOne)();

    // deleted_post_meta hands over an array of meta ids where the other
    // two pass one — the signatures genuinely differ, and neither value
    // matters here.
    $this->invalidator->onMetaChanged([101, 102], 1);

    expect(memberCacheVersion($this->cache))->not->toBe($version);
});

it('invalidates the cache on a member post save', function () {
    Functions\when('get_post_type')->justReturn(MEMBER_POST_TYPE);

    $version = ($this->versionAfterAReadOfMemberOne)();

    $this->invalidator->onPostChanged(1);

    expect(memberCacheVersion($this->cache))->not->toBe($version);
});
