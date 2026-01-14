<?php

declare(strict_types=1);

namespace Unity\Locations\Interfaces;

/**
 * Interface for Locations Repository
 */
interface LocationRepositoryInterface
{
    /**
     * Find a location by ID
     *
     * @param int $id The location ID
     * @return LocationInterface|null The location or null if not found
     */
    public function findById(int $id): ?LocationInterface;

    /**
     * Find all locations
     *
     * @param array $args Optional arguments for querying locations
     * @return array Array of LocationInterface objects
     */
    public function findAll(array $args = []): array;

    /**
     * Find locations by city
     *
     * @param string $city The city name to filter by
     * @return array Array of LocationInterface objects
     */
    public function findByCity(string $city): array;

    /**
     * Find locations by region
     *
     * @param string $region The region name to filter by
     * @return array Array of LocationInterface objects
     */
    public function findByRegion(string $region): array;

    /**
     * Save a location
     *
     * @param LocationInterface $location The location to save
     * @return bool Whether the save was successful
     */
    public function save(LocationInterface $location): bool;

    /**
     * Update a location
     *
     * @param LocationInterface $location The location to update
     * @return bool Whether the update was successful
     */
    public function update(LocationInterface $location): bool;

    /**
     * Delete a location
     *
     * @param int $id The ID of the location to delete
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool;
}
