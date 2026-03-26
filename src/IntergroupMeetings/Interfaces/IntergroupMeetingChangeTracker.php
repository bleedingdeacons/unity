<?php

namespace Unity\IntergroupMeetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Class IntergroupMeetingChangeTracker
 *
 * Tracks changes to intergroup meetings via ACF and fires the
 * unity/intergroup_meeting_changing hook when actual changes are detected.
 */
interface IntergroupMeetingChangeTracker
{
    /**
     * Capture the original intergroup meeting before ACF makes changes
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function captureOriginalMeeting(int $postId): void;

    /**
     * Check for changes after ACF has saved all fields
     *
     * @param int $postId The post ID being saved
     * @return void
     */
    public function checkForChanges(int $postId): void;

    /**
     * Handle intergroup meeting deletion (trash or permanent delete)
     *
     * Captures the intergroup meeting before it is removed and fires the
     * unity/intergroup_meeting_deleted hook so that listeners can react.
     *
     * @param int $postId The post ID being deleted or trashed
     * @return void
     */
    public function onIntergroupMeetingDeleted(int $postId): void;
}