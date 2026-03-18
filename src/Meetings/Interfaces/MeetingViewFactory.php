<?php

declare(strict_types=1);

namespace Unity\Meetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meeting Position View Factory Interface
 */
interface MeetingViewFactory
{
    /**
     * Create a meeting view from a meeting ID
     *
     * @param int $meetingId
     * @return MeetingView|null
     */
    public function createFrom(int $meetingId): ?MeetingView;

}