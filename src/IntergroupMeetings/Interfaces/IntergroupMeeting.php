<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Intergroup Meeting Interface
 */
interface IntergroupMeeting
{
    /**
     * Get the intergroup meeting ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Get the title of the intergroup meeting
     *
     * @return string The meeting title or empty string if not set
     */
    public function getTitle(): string;

    /**
     * Get the array of group IDs attending the meeting
     *
     * @return array<int>
     */
    public function getGroupAttendees(): array;

    /**
     * Get the array of officer IDs attending the meeting
     *
     * @return array<int>
     */
    public function getOfficersAttending(): array;

    /**
     * Get the date of the meeting
     *
     * @return string Date in format Y-m-d or empty string if not set
     */
    public function getDate(): string;

    /**
     * Add a group ID to the group attendees list
     *
     * @param int $groupId
     * @return bool True if the group was added, false if already present
     */
    public function addGroupAttendee(int $groupId): bool;

    /**
     * Remove a group ID from the group attendees list
     *
     * @param int $groupId
     * @return bool True if the group was removed, false if not present
     */
    public function removeGroupAttendee(int $groupId): bool;

    /**
     * Check if a group ID is in the group attendees list
     *
     * @param int $groupId
     * @return bool
     */
    public function hasGroupAttendee(int $groupId): bool;

    /**
     * Add an officer ID to the officers attending list
     *
     * @param int $officerId
     * @return bool True if the officer was added, false if already present
     */
    public function addOfficerAttendee(int $officerId): bool;

    /**
     * Remove an officer ID from the officers attending list
     *
     * @param int $officerId
     * @return bool True if the officer was removed, false if not present
     */
    public function removeOfficerAttendee(int $officerId): bool;

    /**
     * Check if an officer ID is in the officers attending list
     *
     * @param int $officerId
     * @return bool
     */
    public function hasOfficerAttendee(int $officerId): bool;

    /**
     * Get the last updated timestamp
     *
     * @return string Last updated datetime string
     */
    public function getUpdated(): string;
}