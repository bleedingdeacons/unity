<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Officer Attendance Factory Interface
 */
interface IntergroupMeetingOfficerAttendanceFactory
{
    /**
     * Create an IntergroupMeetingOfficerAttendance from a source ID.
     *
     * @param int $id Row ID in the officer attendance table
     * @return IntergroupMeetingOfficerAttendance
     */
    public function createFromSource(int $id): IntergroupMeetingOfficerAttendance;

    /**
     * Create a new IntergroupMeetingOfficerAttendance instance.
     *
     * @param int    $intergroupMeetingId Parent intergroup meeting ID
     * @param int    $officerId           Officer member ID
     * @param string $positionName        Position name (plain text)
     * @param string $officerName         Officer name (plain text)
     * @return IntergroupMeetingOfficerAttendance
     */
    public function createNew(
        int $intergroupMeetingId,
        int $officerId,
        string $positionName,
        string $officerName
    ): IntergroupMeetingOfficerAttendance;
}