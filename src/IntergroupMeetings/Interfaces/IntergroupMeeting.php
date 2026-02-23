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
     * Get the title of the intergroup meeting
     *
     * @return string The meeting title or empty string if not set
     */
    public function getTitle(): string;

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
     * Get the array of group post IDs attending the meeting (ACF field: attending_groups)
     *
     * @return array<int>
     */
    public function getAttendingGroups(): array;

    /**
     * Get the array of officer post IDs attending the meeting (ACF field: attending_officers)
     *
     * @return array<int>
     */
    public function getAttendingOfficers(): array;

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
}