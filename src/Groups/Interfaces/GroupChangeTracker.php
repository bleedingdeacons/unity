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
}