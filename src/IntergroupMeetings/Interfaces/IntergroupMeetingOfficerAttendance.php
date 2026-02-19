<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Officer Attendance Interface
 *
 * Records individual attendance at an intergroup meeting.
 * Uses plain text values for officer and anonymous name (no relationships).
 */
interface IntergroupMeetingOfficerAttendance
{
    /**
     * Get the attendance record ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Get the intergroup meeting ID this attendance belongs to.
     *
     * @return int
     */
    public function getIntergroupMeetingId(): int;

    /**
     * Get the member ID this attendance record belongs to.
     *
     * @return int
     */
    public function getOfficerId(): int;

    /**
     * Get the officer (plain text, no relationship).
     *
     * @return string
     */
    public function getPositionName(): string;

    /**
     * Get the Officer name (plain text, no relationship).
     *
     * @return string
     */
    public function getOfficerName(): string;

}