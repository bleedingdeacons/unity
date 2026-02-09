<?php

declare(strict_types=1);

namespace Unity\Meetings\Interfaces;

/**
 * Interface MeetingFactory
 *
 * Defines the contract for creating Meeting objects.
 */
interface MeetingFactory
{
    /**
     * Create a Meeting object from source data.
     *
     * @param array $source The meeting source data.
     * @return Meeting|null Meeting object or null if creation fails.
     */
    public function createFromSource(array $source): ?Meeting;
}
