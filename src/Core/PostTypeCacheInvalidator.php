<?php

declare(strict_types=1);

namespace Unity\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\Interfaces\CacheBumper;
use function add_action;
use function get_post_type;

/**
 * Clears a cache whenever a post of one type changes, however it changed.
 *
 * Most writes never go through a repository: the ACF admin screens write
 * fields directly, Reconcile imports with wp_insert_post() and update_field(),
 * Scrutiny's pruner erases personal data in place, and TSML's own importer
 * writes meetings. Hooking WordPress's own post and meta actions catches all
 * of them, and catches a change made by a plugin nobody has thought about yet.
 *
 * The meta hooks are the load-bearing ones, and not only for imports. ACF
 * fires them field by field during a save, which puts the clear *before* a
 * change tracker re-reads the post in the same request. Clearing on a
 * unity/*_changed action instead would be too late: the tracker would compare
 * its pre-edit snapshot against a cached copy of itself, find nothing changed,
 * and Scrutiny would never record the edit.
 *
 * Unity ships headless and knows no post types, so the type is injected by
 * whichever plugin supplies the repositories.
 */
class PostTypeCacheInvalidator
{
    public function __construct(
        private readonly CacheBumper $cache,
        private readonly string $postType,
    ) {
    }

    public function register(): void
    {
        add_action('save_post_' . $this->postType, [$this, 'onPostChanged'], 10, 1);
        add_action('trashed_post', [$this, 'onPostChanged'], 10, 1);
        add_action('untrashed_post', [$this, 'onPostChanged'], 10, 1);

        // before_delete_post rather than deleted_post: the post type has to be
        // readable to tell whether the deletion concerned this type at all,
        // and by the time deleted_post fires the row is gone and
        // get_post_type() answers false.
        add_action('before_delete_post', [$this, 'onPostChanged'], 10, 1);

        add_action('added_post_meta', [$this, 'onMetaChanged'], 10, 2);
        add_action('updated_post_meta', [$this, 'onMetaChanged'], 10, 2);
        add_action('deleted_post_meta', [$this, 'onMetaChanged'], 10, 2);
    }

    public function onPostChanged(int $postId): void
    {
        $this->bumpIfMine($postId);
    }

    /**
     * @param mixed $metaId Ignored. Singular for added/updated, an array of
     *                      ids for deleted — the signatures differ, and
     *                      neither value is of any use here.
     */
    public function onMetaChanged(mixed $metaId, int $postId): void
    {
        $this->bumpIfMine($postId);
    }

    private function bumpIfMine(int $postId): void
    {
        if (get_post_type($postId) !== $this->postType) {
            return;
        }

        $this->cache->bump();
    }
}
