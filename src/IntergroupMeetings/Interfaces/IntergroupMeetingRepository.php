<?php


declare(strict_types=1);

namespace Unity\IntergroupMeetings\Interfaces;

/**
 * Intergroup Meeting Repository Interface
 */
interface IntergroupMeetingRepository
{
    /**
     * Find an intergroup meeting by ID
     *
     * @param int $id Intergroup Meeting ID
     * @return IntergroupMeeting|null
     */
    public function find(int $id): ?IntergroupMeeting;

    /**
     * Find all intergroup meetings matching the given arguments
     *
     * @param array $args Query arguments
     * @return array<IntergroupMeeting>
     */
    public function findAll(array $args = []): array;

    /**
     * Get total count of intergroup meetings matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Save an intergroup meeting
     *
     * @param IntergroupMeeting $intergroupMeeting
     * @return bool Success status
     */
    public function save(IntergroupMeeting $intergroupMeeting): bool;

    /**
     * Delete an intergroup meeting
     *
     * @param int $id Intergroup Meeting ID
     * @return bool Success status
     */
    public function delete(int $id): bool;
}