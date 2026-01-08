<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

/**
 * Member Repository Interface
 */
interface MemberRepositoryInterface
{
    /**
     * Find a member by ID
     *
     * @param int $id Member ID
     * @return MemberInterface|null
     */
    public function find(int $id): ?MemberInterface;

    /**
     * Find all members matching the given arguments
     *
     * @param array $args Query arguments
     * @return array Array of MemberInterface objects
     */
    public function findAll(array $args = []): array;

    /**
     * Save a member
     *
     * @param MemberInterface $member
     * @return bool Success status
     */
    public function save(MemberInterface $member): bool;

    /**
     * Delete a member
     *
     * @param int $id Member ID
     * @return bool Success status
     */
    public function delete(int $id): bool;
}
