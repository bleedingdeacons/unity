<?php

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Class MemberChangeTracker
 *
 * Tracks changes to members via ACF and fires the unity/member_changing hook
 * when actual changes are detected.
 */
interface MemberChangeTracker
{
    /**
     * Capture the original member before ACF makes changes
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function captureOriginalMember(int $postId): void;

    /**
     * Check for changes after ACF has saved all fields
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function checkForChanges(int $postId): void;

    /**
     * Handle member deletion (trash or permanent delete)
     *
     * Captures the member before it is removed and fires the
     * unity/member_deleted hook so that listeners can react.
     *
     * @param int $postId The post ID being deleted or trashed
     * @return void
     */
    public function onMemberDeleted(int $postId): void;
}