<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Locations\Interfaces\Location;
use Unity\Meetings\Interfaces\Meeting;

/**
 * An inert Meeting value object for tests.
 *
 * getDayOfWeek() is derived from getDay() rather than separately settable:
 * they are two views of one fact, and letting a test set Tuesday's number
 * alongside Wednesday's name would let a consumer read the wrong one and pass.
 *
 * getLocation() defaults to null, which is the case worth defaulting to — it
 * is nullable on the interface, an online-only meeting genuinely has none, and
 * a consumer that dereferences it blind should fail on the cheapest possible
 * test.
 *
 * @phpstan-consistent-constructor
 */
class MeetingStub implements Meeting
{
    private const DAY_NAMES = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    /**
     * @param array<int, string> $types
     * @param array<int, mixed> $contacts
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private int $id = 0,
        private string $name = '',
        private string $slug = '',
        private ?Location $location = null,
        private string $url = '',
        private int $day = 0,
        private string $time = '',
        private string $endTime = '',
        private array $types = [],
        private string $state = '',
        private bool $online = false,
        private array $contacts = [],
        private array $meta = [],
        private string $onlineLink = '',
        private string $onlineNotes = '',
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * Derived from getDay() — see the class docblock. An out-of-range day
     * yields an empty string rather than an error, matching how the real
     * implementations treat unset meta.
     */
    public function getDayOfWeek(): string
    {
        return self::DAY_NAMES[$this->day] ?? '';
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getOnlineLink(): string
    {
        return $this->onlineLink;
    }

    public function getOnlineNotes(): string
    {
        return $this->onlineNotes;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }
}
