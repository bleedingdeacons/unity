<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

/**
 * Interface for Group View
 */
interface GroupView
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
     * Get the email address of the group
     * 
     * @return string The email address
     */
    public function getEmail(): string;

    /**
     * Get the meetings associated with this group
     * 
     * @return array Array of Meeting objects
     */
    public function getMeetings(): array;

    /**
     * Get the group's link
     * 
     * @return string Link URL
     */
    public function getLink(): string;

    /**
     * Get the contacts associated with this group
     *
     * @return \Unity\Contacts\Interfaces\Contact[] Array of Contact objects
     */
    public function getContacts(): array;
}
