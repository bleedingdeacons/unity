<?php

declare(strict_types=1);

namespace Unity\Members;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use function add_action;
use function get_post_type;

/**
 * Clears {@see CachingMemberRepository} when a member changes anywhere.
 *
 * Most member writes never go through the repository: the ACF admin screen
 * writes fields directly, Reconcile imports with wp_insert_post() and
 * update_field(), and Scrutiny's pruner erases personal data in place. Hooking
 * WordPress's own post and meta actions catches all of them, and catches a
 * change made by a plugin nobody has thought about yet.
 *
 * The meta hooks are the load-bearing ones, and not only for imports. ACF
 * fires them field by field during a save, which puts the clear *before*
 * TsmlMemberChangeTracker re-reads the member in checkForChanges(). Clearing
 * on unity/member_changed instead would be too late: the tracker would compare
 * the pre-edit snapshot against a cached copy of itself, find nothing changed,
 * and Scrutiny would never record the edit.
 *
 * Unity ships headless and knows no post types, so the member post type is
 * injected by whichever plugin supplies the repositories.
 */
final class MemberCacheInvalidator
{
    public function __construct(
        private readonly CachingMemberRepository $repository,
        private readonly string $postType,
    ) {
    }

    public function register(): void
    {
        add_action('save_post_' . $this->postType, [$this, 'onPostChanged'], 10, 1);
        add_action('trashed_post', [$this, 'onPostChanged'], 10, 1);
        add_action('untrashed_post', [$this, 'onPostChanged'], 10, 1);

        // before_delete_post rather than deleted_post: the post type has to be
        // readable to tell whether the deletion concerned a member at all, and
        // by the time deleted_post fires the row is gone and get_post_type()
        // answers false.
        add_action('before_delete_post', [$this, 'onPostChanged'], 10, 1);

        add_action('added_post_meta', [$this, 'onMetaChanged'], 10, 2);
        add_action('updated_post_meta', [$this, 'onMetaChanged'], 10, 2);
        add_action('deleted_post_meta', [$this, 'onMetaChanged'], 10, 2);
    }

    public function onPostChanged(int $postId): void
    {
        $this->bumpIfMember($postId);
    }

    /**
     * @param mixed $metaId Ignored. Singular for added/updated, an array of
     *                      ids for deleted — the signatures differ, and
     *                      neither value is of any use here.
     */
    public function onMetaChanged(mixed $metaId, int $postId): void
    {
        $this->bumpIfMember($postId);
    }

    private function bumpIfMember(int $postId): void
    {
        if (get_post_type($postId) !== $this->postType) {
            return;
        }

        $this->repository->bump();
    }
}
