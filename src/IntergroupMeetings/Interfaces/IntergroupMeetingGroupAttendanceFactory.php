<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Intergroup Meeting Attendance Factory Interface
 */
interface IntergroupMeetingGroupAttendanceFactory
{
    /**
     * Create an IntergroupMeetingGroupAttendance from a source ID.
     *
     * @param int $id Row ID in the attendance table
     * @return IntergroupMeetingGroupAttendance
     */
    public function createFromSource(int $id): IntergroupMeetingGroupAttendance;

    /**
     * Create a new IntergroupMeetingGroupAttendance instance.
     *
     * @param int    $intergroupMeetingId Parent intergroup meeting ID
     * @param int    $groupId            Group CPT post ID
     * @param int    $memberId           Member ID
     * @param string $meetingGroup        Meeting or group name (looked up from group CPT)
     * @param string $gsrName            GSR name (plain text)
     * @param bool   $gsrProxy           Whether a proxy attended for the GSR
     * @param string $gsrProxyName       Proxy name (plain text)
     * @return IntergroupMeetingGroupAttendance
     */
    public function createNew(
        int $intergroupMeetingId,
        int $groupId,
        int $memberId,
        string $meetingGroup,
        string $gsrName,
        bool $gsrProxy = false,
        string $gsrProxyName = ''
    ): IntergroupMeetingGroupAttendance;
}