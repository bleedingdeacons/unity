<?php

namespace Unity\Groups\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Class GroupChangeTracker
 *
 * Tracks changes to groups via ACF and fires the unity/group_changing hook
 * when actual changes are detected.
 */
interface GroupChangeTracker
{
    /**
     * Capture the original group before ACF makes changes
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function captureOriginalGroup(int $postId): void;

    /**
     * Check for changes after ACF has saved all fields
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function checkForChanges(int $postId): void;

    /**
     * Handle group deletion (trash or permanent delete)
     *
     * Captures the group before it is removed and fires the
     * unity/group_deleted hook so that listeners can react.
     *
     * @param int $postId The post ID being deleted or trashed
     * @return void
     */
    public function onGroupDeleted(int $postId): void;

    /**
     * Handle a group being hidden (post status set to private)
     *
     * Fires the unity/group_hidden hook when a group's publish state
     * transitions to private.
     *
     * @param string $newStatus The new post status
     * @param string $oldStatus The previous post status
     * @param \WP_Post $post The post object
     * @return void
     */
    public function onGroupHidden(string $newStatus, string $oldStatus, \WP_Post $post): void;
}