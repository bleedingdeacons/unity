<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Attendance Interface
 *
 * Records individual attendance at an intergroup meeting.
 * Uses plain text values for meeting/group and GSR name (no relationships).
 */
interface IntergroupMeetingAttendance
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
    public function getMemberId(): int;

    /**
     * Get the meeting or group name (plain text, no relationship).
     *
     * @return string
     */
    public function getMeetingGroup(): string;

    /**
     * Get the GSR name (plain text, no relationship).
     *
     * @return string
     */
    public function getGsrName(): string;

    /**
     * Check if a proxy attended in place of the GSR.
     *
     * @return bool
     */
    public function isGsrProxy(): bool;

    /**
     * Get the proxy name when a proxy attended for the GSR.
     *
     * @return string Proxy name or empty string if no proxy.
     */
    public function getGsrProxyName(): string;
}