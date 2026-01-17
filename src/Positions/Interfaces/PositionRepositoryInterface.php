<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

/**
 * Interface for Position Repository
 */
interface PositionRepositoryInterface
{
    /**
     * Find a position by ID
     *
     * @param int $id The position ID
     * @return PositionInterface|null The position or null if not found
     */
    public function findById(int $id): ?PositionInterface;

    /**
     * Find all positions
     *
     * @param array $args Optional arguments for querying positions
     * @return array Array of PositionInterface objects
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
     * @param PositionInterface $position The position to save
     * @return bool Whether the save was successful
     */
    public function save(PositionInterface $position): bool;

    /**
     * Update a position
     *
     * @param PositionInterface $position The position to update
     * @return bool Whether the update was successful
     */
    public function update(PositionInterface $position): bool;

    /**
     * Delete a position
     *
     * @param int $id The ID of the position to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool;
}