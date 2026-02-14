<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

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
     * Get the array of member IDs attending the meeting
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
     * Add a member ID to the group attendees list
     *
     * @param int $memberId
     * @return bool True if the member was added, false if already present
     */
    public function addGroupAttendee(int $memberId): bool;

    /**
     * Remove a member ID from the group attendees list
     *
     * @param int $memberId
     * @return bool True if the member was removed, false if not present
     */
    public function removeGroupAttendee(int $memberId): bool;

    /**
     * Check if a member ID is in the group attendees list
     *
     * @param int $memberId
     * @return bool
     */
    public function hasGroupAttendee(int $memberId): bool;
}