<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

use Unity\Contact\Interfaces\ContactInterface;

/**
 * Interface for Group entity
 */
interface GroupInterface
{
    /**
     * Get the ID of the group
     * 
     * @return int The group ID
     */
    public function getId(): int;

    /**
     * Get the title of the group
     * 
     * @return string The group title
     */
    public function getTitle(): string;

    /**
     * Get the group's email address
     * 
     * @return string Email address
     */
    public function getEmail(): string;

    /**
     * Get the meeting IDs associated with this group
     * 
     * @return array Array of meeting IDs
     */
    public function getMeetingIds(): array;

    /**
     * Get the group's link
     * 
     * @return string Link URL
     */
    public function getLink(): string;

    /**
     * Check if the current group is valid
     * 
     * @return bool Whether the group is valid
     */
    public function isValid(): bool;

    /**
     * Get the group notes/description
     * 
     * @return string Group notes
     */
    public function getGroupNotes(): string;

    /**
     * Get the group website URL
     * 
     * @return string Website URL
     */
    public function getWebsite(): string;

    /**
     * Get the group phone number
     * 
     * @return string Phone number
     */
    public function getPhone(): string;

    /**
     * Get the Venmo handle for 7th Tradition contributions
     * 
     * @return string Venmo handle (e.g., @AAGroupName)
     */
    public function getVenmo(): string;

    /**
     * Get the PayPal username for 7th Tradition contributions
     * 
     * @return string PayPal username
     */
    public function getPaypal(): string;

    /**
     * Get the Square Cash App cashtag for 7th Tradition contributions
     * 
     * @return string Square cashtag (e.g., $AAGroupName)
     */
    public function getSquare(): string;

    /**
     * Get the district ID
     * 
     * @return int|null District ID or null if not set
     */
    public function getDistrictId(): ?int;

    /**
     * Get the last contact timestamp
     * 
     * @return string|null Last contact timestamp or null if not set
     */
    public function getLastContact(): ?string;

    /**
     * Get the contacts array
     * 
     * @return ContactInterface[] Array of Contact objects.
     */
    public function getContacts(): array;

    /**
     * Check if the group has any digital contribution options
     * 
     * @return bool True if any contribution option is available
     */
    public function hasContributionOptions(): bool;
}
