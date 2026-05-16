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
     * Find a member by personal email address
     *
     * Looks up a member by their personal email ACF field. Email
     * addresses are expected to be unique across members; if more than
     * one match exists, the first is returned.
     *
     * @param string $email Email address to search for
     * @return Member|null The matching member, or null if none found
     */
    public function findByEmail(string $email): ?Member;

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
     * Create a new member with just an anonymous name
     *
     * Inserts a fresh member record and returns the new ID. Other fields
     * are left at defaults and can be written in a follow-up save() call —
     * this mirrors the two-phase admin form flow, where a post is created
     * first and ACF fields are filled in on subsequent saves.
     *
     * @param string $anonymousName The anonymous name for the new member
     * @return int The new member ID, or 0 if creation failed
     */
    public function create(string $anonymousName): int;

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

    /**
     * Update a member
     *
     * @param Member $member The member to update
     * @return bool Whether the update was successful
     */
    public function update(Member $member): bool;

}
