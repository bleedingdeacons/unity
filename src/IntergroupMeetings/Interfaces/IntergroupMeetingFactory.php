<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Intergroup Meeting Factory Interface
 */
interface IntergroupMeetingFactory
{
    /**
     * Create an IntergroupMeeting from a source ID
     *
     * @param int $id WordPress post ID
     * @return IntergroupMeeting
     */
    public function createFromSource(int $id): IntergroupMeeting;
}