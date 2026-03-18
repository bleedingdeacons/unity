<?php

declare(strict_types=1);

namespace Unity\Meetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Members\Interfaces\Member;
use DateTime;

/**
 * Position View Interface
 *
 * Combines meeting and member data for presentation
 */
interface MeetingView
{
    /**
     * Get the meeting object
     *
     * @return Meeting
     */
    public function getMeeting(): Meeting;

    /**
     * Get the members associated with this meeting
     *
     * @return Member[]|null
     */
    public function getMembers(): array;

    /**
     * Get the names of associated members in this meeting
     *
     * @return String[]
     */
    public function getGsrNames(): array;
}