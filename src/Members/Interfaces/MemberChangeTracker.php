<?php

namespace Unity\Members\Interfaces;


/**
 * Class MemberChangeTracker
 *
 * Tracks changes to members via ACF and fires the member_changed hook
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
}