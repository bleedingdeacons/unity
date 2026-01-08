<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

/**
 * Interface for Group Repository
 */
interface GroupRepositoryInterface
{
    /**
     * Find a group by ID
     * 
     * @param int $id The group ID
     * @return GroupInterface|null The group or null if not found
     */
    public function findById(int $id): ?GroupInterface;

    /**
     * Find all groups
     * 
     * @param array $args Optional arguments for querying groups
     * @return array Array of GroupInterface objects
     */
    public function findAll(array $args = []): array;

    /**
     * Save a group
     * 
     * @param GroupInterface $group The group to save
     * @return bool Whether the save was successful
     */
    public function save(GroupInterface $group): bool;

    /**
     * Update a group
     * 
     * @param GroupInterface $group The group to update
     * @return bool Whether the update was successful
     */
    public function update(GroupInterface $group): bool;

    /**
     * Delete a group
     * 
     * @param int $id The ID of the group to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool;
}
