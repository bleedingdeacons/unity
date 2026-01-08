<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

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
}
