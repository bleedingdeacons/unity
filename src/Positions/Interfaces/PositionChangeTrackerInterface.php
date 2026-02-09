<?php

namespace Unity\Positions\Interfaces;


/**
 * Class PositionChangeTracker
 *
 * Tracks changes to positions via ACF and fires the position_changed hook
 * when actual changes are detected.
 */
interface PositionChangeTrackerInterface
{
    /**
     * Capture the original position before ACF makes changes
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function captureOriginalPosition(int $postId): void;

    /**
     * Check for changes after ACF has saved all fields
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function checkForChanges(int $postId): void;
}