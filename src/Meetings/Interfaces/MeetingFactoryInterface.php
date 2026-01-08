<?php

declare(strict_types=1);

namespace Unity\Meetings\Interfaces;

/**
 * Interface MeetingFactoryInterface
 *
 * Defines the contract for creating Meeting objects.
 */
interface MeetingFactoryInterface
{
    /**
     * Create a Meeting object from source data.
     *
     * @param array $source The meeting source data.
     * @return MeetingInterface|null Meeting object or null if creation fails.
     */
    public function createFromSource(array $source): ?MeetingInterface;
}
