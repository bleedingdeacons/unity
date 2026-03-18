<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Group Repository
 */
interface GroupRepository
{
    /**
     * Find a group by ID
     * 
     * @param int $id The group ID
     * @return Group|null The group or null if not found
     */
    public function findById(int $id): ?Group;

    /**
     * Find all groups
     * 
     * @param array $args Optional arguments for querying groups
     * @return array Array of Group objects
     */
    public function findAll(array $args = []): array;

    /**
     * Save a group
     * 
     * @param Group $group The group to save
     * @return bool Whether the save was successful
     */
    public function save(Group $group): bool;

    /**
     * Update a group
     * 
     * @param Group $group The group to update
     * @return bool Whether the update was successful
     */
    public function update(Group $group): bool;

    /**
     * Delete a group
     * 
     * @param int $id The ID of the group to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool;
}
