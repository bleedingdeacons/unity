<?php

declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Interface
 */
interface IntergroupMeetingInterface
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
    public function getAttendees(): array;

    /**
     * Get the date of the meeting
     *
     * @return string Date in format Y-m-d or empty string if not set
     */
    public function getDate(): string;
}