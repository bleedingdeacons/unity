<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

/**
 * Interface for Position Repository
 */
interface PositionRepository
{
    /**
     * Find a position by ID
     *
     * @param int $id The position ID
     * @return Position|null The position or null if not found
     */
    public function findById(int $id): ?Position;

    /**
     * Find all positions
     *
     * @param array $args Optional arguments for querying positions
     * @return array Array of Position objects
     */
    public function findAll(array $args = []): array;

    /**
     * Get total count of positions matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     */
    public function count(array $args = []): int;

    /**
     * Save a position
     *
     * @param Position $position The position to save
     * @return bool Whether the save was successful
     */
    public function save(Position $position): bool;

    /**
     * Update a position
     *
     * @param Position $position The position to update
     * @return bool Whether the update was successful
     */
    public function update(Position $position): bool;

    /**
     * Delete a position
     *
     * @param int $id The ID of the position to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool;
}