<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Attendance Factory Interface
 */
interface IntergroupMeetingAttendanceFactory
{
    /**
     * Create an IntergroupMeetingAttendance from a source ID.
     *
     * @param int $id Row ID in the attendance table
     * @return IntergroupMeetingAttendance
     */
    public function createFromSource(int $id): IntergroupMeetingAttendance;

    /**
     * Create a new IntergroupMeetingAttendance instance.
     *
     * @param int    $intergroupMeetingId Parent intergroup meeting ID
     * @param int    $memberId           Member ID
     * @param string $meetingGroup        Meeting or group name (plain text)
     * @param string $gsrName            GSR name (plain text)
     * @param bool   $gsrProxy           Whether a proxy attended for the GSR
     * @param string $gsrProxyName       Proxy name (plain text)
     * @return IntergroupMeetingAttendance
     */
    public function createNew(
        int $intergroupMeetingId,
        int $memberId,
        string $meetingGroup,
        string $gsrName,
        bool $gsrProxy = false,
        string $gsrProxyName = ''
    ): IntergroupMeetingAttendance;
}