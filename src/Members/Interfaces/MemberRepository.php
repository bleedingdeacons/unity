<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member Repository Interface
 */
interface MemberRepository
{
    /**
     * Find a member by ID
     *
     * @param int $id Member ID
     * @return Member|null
     */
    public function findById(int $id): ?Member;

    /**
     * Find all members matching the given arguments
     *
     * @param array $args Query arguments
     * @return array Array of Member objects
     */
    public function findAll(array $args = []): array;

    /**
     * Get total count of members matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Save a member
     *
     * @param Member $member
     * @return bool Success status
     */
    public function save(Member $member): bool;

    /**
     * Delete a member
     *
     * @param int $id Member ID
     * @return bool Success status
     */
    public function delete(int $id): bool;
}