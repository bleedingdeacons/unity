<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Officer Attendance Repository Interface
 */
interface IntergroupMeetingOfficerAttendanceRepository
{
    /**
     * Find an officer attendance record by ID.
     *
     * @param int $id Attendance record ID
     * @return IntergroupMeetingOfficerAttendance|null
     */
    public function find(int $id): ?IntergroupMeetingOfficerAttendance;

    /**
     * Find all officer attendance records matching the given arguments.
     *
     * @param array $args Query arguments
     * @return array<IntergroupMeetingOfficerAttendance>
     */
    public function findAll(array $args = []): array;

    /**
     * Find all officer attendance records for a specific intergroup meeting.
     *
     * @param int $intergroupMeetingId
     * @return array<IntergroupMeetingOfficerAttendance>
     */
    public function findByIntergroupMeeting(int $intergroupMeetingId): array;

    /**
     * Get total count of officer attendance records matching criteria.
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Save an officer attendance record.
     *
     * @param IntergroupMeetingOfficerAttendance $attendance
     * @return bool Success status
     */
    public function save(IntergroupMeetingOfficerAttendance $attendance): bool;

    /**
     * Delete an officer attendance record.
     *
     * @param int $id Attendance record ID
     * @return bool Success status
     */
    public function delete(int $id): bool;

    /**
     * Delete the officer attendance record for a specific officer at a specific intergroup meeting.
     *
     * @param int $intergroupMeetingId
     * @param int $officerId
     * @return bool Success status
     */
    public function deleteByIntergroupMeetingAndOfficer(int $intergroupMeetingId, int $officerId): bool;
}