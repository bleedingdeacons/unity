<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Attendance Repository Interface
 */
interface IntergroupMeetingGroupAttendanceRepository
{
    /**
     * Find an attendance record by ID.
     *
     * @param int $id Attendance record ID
     * @return IntergroupMeetingGroupAttendance|null
     */
    public function find(int $id): ?IntergroupMeetingGroupAttendance;

    /**
     * Find all attendance records matching the given arguments.
     *
     * @param array $args Query arguments
     * @return array<IntergroupMeetingGroupAttendance>
     */
    public function findAll(array $args = []): array;

    /**
     * Find all attendance records for a specific intergroup meeting.
     *
     * @param int $intergroupMeetingId
     * @return array<IntergroupMeetingGroupAttendance>
     */
    public function findByIntergroupMeeting(int $intergroupMeetingId): array;

    /**
     * Get total count of attendance records matching criteria.
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Save an attendance record.
     *
     * @param IntergroupMeetingGroupAttendance $attendance
     * @return bool Success status
     */
    public function save(IntergroupMeetingGroupAttendance $attendance): bool;

    /**
     * Delete an attendance record.
     *
     * @param int $id Attendance record ID
     * @return bool Success status
     */
    public function delete(int $id): bool;

    /**
     * Delete the attendance record for a specific member at a specific intergroup meeting.
     *
     * @param int $intergroupMeetingId
     * @param int $memberId
     * @return bool Success status
     */
    public function deleteByIntergroupMeetingAndMember(int $intergroupMeetingId, int $memberId): bool;
}