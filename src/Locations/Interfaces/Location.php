<?php

declare(strict_types=1);

namespace Unity\Locations\Interfaces;

/**
 * Interface for Locations entity
 */
interface Location
{
    /**
     * Get the ID of the location
     *
     * @return int The location ID
     */
    public function getId(): int;

    /**
     * Get the name/title of the location
     *
     * @return string The location name
     */
    public function getName(): string;

    /**
     * Get the street address
     *
     * @return string Street address
     */
    public function getAddress(): string;

    /**
     * Get the city
     *
     * @return string City name
     */
    public function getCity(): string;

    /**
     * Get the state/province
     *
     * @return string State or province
     */
    public function getState(): string;

    /**
     * Get the postal/zip code
     *
     * @return string Postal code
     */
    public function getPostalCode(): string;

    /**
     * Get the country
     *
     * @return string Country name
     */
    public function getCountry(): string;

    /**
     * Get the region
     *
     * @return string Region name
     */
    public function getRegion(): string;

    /**
     * Get the location notes
     *
     * @return string Locations notes/description
     */
    public function getNotes(): string;

    /**
     * Get the location's permalink URL
     *
     * @return string Permalink URL
     */
    public function getLink(): string;

    /**
     * Get the latitude coordinate
     *
     * @return float|null Latitude or null if not set
     */
    public function getLatitude(): ?float;

    /**
     * Get the longitude coordinate
     *
     * @return float|null Longitude or null if not set
     */
    public function getLongitude(): ?float;

    /**
     * Get the timezone
     *
     * @return string Timezone identifier
     */
    public function getTimezone(): string;

    /**
     * Get the meeting IDs associated with this location
     *
     * @return array Array of meeting IDs
     */
    public function getMeetingIds(): array;

    /**
     * Check if the current location is valid
     *
     * @return bool Whether the location is valid
     */
    public function isValid(): bool;

    /**
     * Get the formatted full address
     *
     * @return string Full formatted address
     */
    public function getFormattedAddress(): string;

    /**
     * Check if the location has coordinates
     *
     * @return bool True if both latitude and longitude are set
     */
    public function hasCoordinates(): bool;
}