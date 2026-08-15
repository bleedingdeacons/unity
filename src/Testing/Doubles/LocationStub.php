<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Locations\Interfaces\Location;

/**
 * An inert Location value object for tests.
 *
 * Mostly needed as the thing {@see MeetingStub::getLocation()} returns, since
 * that accessor is nullable and consumers have to handle both branches — an
 * online-only meeting legitimately has no location.
 *
 * getFormattedAddress() and hasCoordinates() are derived from the other fields
 * rather than separately settable, because on the real implementation they are
 * derived too: a double that let a test set a formatted address inconsistent
 * with its parts would hide a consumer reading the wrong one.
 *
 * @phpstan-consistent-constructor
 */
class LocationStub implements Location
{
    /**
     * @param array<int, int> $meetingIds
     */
    public function __construct(
        private int $id = 0,
        private string $name = '',
        private string $address = '',
        private string $city = '',
        private string $state = '',
        private string $postalCode = '',
        private string $country = '',
        private string $region = '',
        private string $notes = '',
        private string $link = '',
        private ?float $latitude = null,
        private ?float $longitude = null,
        private string $timezone = '',
        private array $meetingIds = [],
        private bool $valid = true,
        private string $updated = ''
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getMeetingIds(): array
    {
        return $this->meetingIds;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Derived, not settable — see the class docblock.
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ], static fn (string $part): bool => $part !== '');

        return implode(', ', $parts);
    }

    /**
     * Derived, not settable — see the class docblock.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }
}
