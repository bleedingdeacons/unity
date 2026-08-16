<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Groups\Interfaces\Group;
use Unity\Meetings\Interfaces\Meeting;

/**
 * An inert Group value object for tests.
 *
 * The companion to {@see MemberStub}, and here for the same reason: Unity
 * ships no concrete Group, so a consumer wanting a plain, predictable one has
 * to write it, and every consumer that does writes a slightly different
 * seventeen-accessor class. Shipping it from Unity makes drift impossible —
 * adding a method to Group breaks Unity's own build before any consumer sees
 * it.
 *
 * Every field is defaulted, so a test names only what it cares about:
 *
 *     new GroupStub(id: 3, title: 'Harbourside Group')
 *
 * Note getMeetings() returns hydrated Meeting objects, not ids — that is the
 * real contract, and a double that returned ids would let a consumer ship code
 * that breaks against the live repository.
 *
 * Not final, for the same reason MemberStub is not: a plugin whose groups are
 * all of one flavour can subclass this to fix its defaults in one place.
 *
 * @phpstan-consistent-constructor
 */
class GroupStub implements Group
{
    /**
     * @param array<int, Meeting> $meetings
     * @param array<int, mixed> $contacts
     */
    public function __construct(
        private int $id = 0,
        private string $title = '',
        private string $email = '',
        private array $meetings = [],
        private string $phone = '',
        private string $website = '',
        private ?int $districtId = null,
        private string $groupNotes = '',
        private array $contacts = [],
        private string $venmo = '',
        private string $paypal = '',
        private string $square = '',
        private ?string $lastContact = null,
        private bool $contributionOptions = false,
        private bool $valid = true,
        private string $link = '',
        private string $updated = ''
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMeetings(): array
    {
        return $this->meetings;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getGroupNotes(): string
    {
        return $this->groupNotes;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getVenmo(): string
    {
        return $this->venmo;
    }

    public function getPaypal(): string
    {
        return $this->paypal;
    }

    public function getSquare(): string
    {
        return $this->square;
    }

    public function getDistrictId(): ?int
    {
        return $this->districtId;
    }

    public function getLastContact(): ?string
    {
        return $this->lastContact;
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    public function hasContributionOptions(): bool
    {
        return $this->contributionOptions;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }
}
