<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Positions\Interfaces\Position;

/**
 * An inert Position value object for tests.
 *
 * The smallest of the entity stubs — a service position is ten accessors and
 * no relations — but worth shipping alongside the others so a consumer testing
 * "which position does this member hold" does not have to hand-roll one.
 *
 * @phpstan-consistent-constructor
 */
class PositionStub implements Position
{
    public function __construct(
        private int $id = 0,
        private string $longName = '',
        private int $minimumSobriety = 0,
        private int $termYears = 0,
        private string $email = '',
        private string $shortDescription = '',
        private string $summary = '',
        private string $link = '',
        private bool $valid = true,
        private string $updated = ''
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMinimumSobriety(): int
    {
        return $this->minimumSobriety;
    }

    public function getTermYears(): int
    {
        return $this->termYears;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLongName(): string
    {
        return $this->longName;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }
}
