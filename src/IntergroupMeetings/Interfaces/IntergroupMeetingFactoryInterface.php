<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Factory Interface
 */
interface IntergroupMeetingFactoryInterface
{
    /**
     * Create an IntergroupMeeting from a source ID
     *
     * @param int $id WordPress post ID
     * @return IntergroupMeetingInterface
     */
    public function createFromSource(int $id): IntergroupMeetingInterface;
}