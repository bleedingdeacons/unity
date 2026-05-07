<?php

declare(strict_types=1);

namespace Unity\PrivacyPolicies\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy Repository Interface
 *
 * Persistence contract for PrivacyPolicy entities, mirroring the shape
 * of MemberRepository: findById/findAll/count/create/save/update/delete.
 *
 * Adds one domain-specific method, findActive(), because in practice
 * almost every consumer (acceptance forms, reconciliation, REST exposure)
 * cares about exactly one row — the policy currently in force. Pushing
 * that selection into the repository keeps the "only one active at a
 * time" invariant in a single place rather than scattered across callers.
 */
interface PrivacyPolicyRepository
{
    /**
     * Find a privacy policy by ID
     *
     * @param int $id Post ID
     * @return PrivacyPolicy|null Null if no policy exists with this ID
     */
    public function findById(int $id): ?PrivacyPolicy;

    /**
     * Find the currently active privacy policy
     *
     * Returns the single policy with `gdpr-policy-active` set to true.
     * If no policy is active, returns null. If more than one policy is
     * marked active (which should not happen), implementations should
     * return the most recently modified one and may log a warning.
     *
     * @return PrivacyPolicy|null
     */
    public function findActive(): ?PrivacyPolicy;

    /**
     * Find all privacy policies matching the given arguments
     *
     * @param array $args Query arguments (passed through to the underlying
     *                    storage; defaults to all published policies)
     * @return array Array of PrivacyPolicy objects
     */
    public function findAll(array $args = []): array;

    /**
     * Get total count of privacy policies matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Create a new privacy policy with just a title
     *
     * Inserts a fresh post and returns the new ID. Other fields are left
     * at defaults and can be written in a follow-up save() call — this
     * mirrors the two-phase flow used elsewhere in Unity, where a post
     * is created first and ACF fields are filled in on subsequent saves.
     *
     * @param string $title The title for the new policy
     * @return int The new post ID, or 0 if creation failed
     */
    public function create(string $title): int;

    /**
     * Save a privacy policy (insert or update)
     *
     * If the policy has an ID > 0, delegates to update(); otherwise inserts
     * a new post and writes the ACF fields.
     *
     * @param PrivacyPolicy $policy
     * @return bool Success status
     */
    public function save(PrivacyPolicy $policy): bool;

    /**
     * Update an existing privacy policy
     *
     * @param PrivacyPolicy $policy
     * @return bool Success status
     */
    public function update(PrivacyPolicy $policy): bool;

    /**
     * Delete a privacy policy
     *
     * @param int $id Post ID
     * @return bool Success status
     */
    public function delete(int $id): bool;
}
