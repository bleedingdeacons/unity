<?php

namespace Unity\Groups\Interfaces;


/**
 * Class GroupChangeTracker
 *
 * Tracks changes to groups via ACF and fires the group_changed hook
 * when actual changes are detected.
 */
interface GroupChangeTrackerInterface
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