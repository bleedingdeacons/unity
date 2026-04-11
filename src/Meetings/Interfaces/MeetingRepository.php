<?php

declare(strict_types=1);

namespace Unity\Meetings\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MeetingRepository
 *
 * Defines the contract for retrieving Meeting objects from the data store.
 */
interface MeetingRepository
{
    /**
     * Find a meeting by ID.
     *
     * @param int $id Meeting ID
     * @return Meeting|null Meeting object or null if not found
     */
    public function findById(int $id): ?Meeting;

    /**
     * Find all meetings with optional query arguments.
     *
     * @param array $args Query arguments (posts_per_page, paged, etc.)
     * @return Meeting[] Array of Meeting objects
     */
    public function findAll(array $args = []): array;

    /**
     * Find meetings by day of week.
     *
     * @param int $day Day of week (0-6, Sunday=0)
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function findByDay(int $day, array $args = []): array;

    /**
     * Find online meetings.
     *
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function findOnline(array $args = []): array;

    /**
     * Find in-person meetings.
     *
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function findInPerson(array $args = []): array;

    /**
     * Find meetings by group ID.
     *
     * @param int $groupId Group ID
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function findByGroupId(int $groupId, array $args = []): array;

    /**
     * Find meetings by location ID.
     *
     * @param int $locationId Location ID
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function findByLocationId(int $locationId, array $args = []): array;

    /**
     * Search meetings by keyword.
     *
     * @param string $keyword Search keyword
     * @param array $args Additional query arguments
     * @return Meeting[] Array of Meeting objects
     */
    public function search(string $keyword, array $args = []): array;

    /**
     * Get total count of meetings matching criteria.
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;
}