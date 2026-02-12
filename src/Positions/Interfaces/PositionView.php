<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

use Unity\Members\Interfaces\Member;
use DateTime;

/**
 * Position View Interface
 * 
 * Combines position and member data for presentation
 */
interface PositionView
{
    /**
     * Get the position object
     * 
     * @return Position
     */
    public function getPosition(): Position;

    /**
     * Get the member assigned to this position
     * 
     * @return Member|null
     */
    public function getMember(): ?Member;

    /**
     * Check if the position has a member assigned
     * 
     * @return bool
     */
    public function isVacant(): bool;

    /**
     * Get the number of days until the position rotates
     * 
     * @return int|null Number of days or null if no rotation date set
     */
    public function getDaysUntilRotation(): ?int;

    /**
     * Get the number of months until the position rotates
     * 
     * @return int|null Number of months or null if no rotation date set
     */
    public function getMonthsUntilRotation(): ?int;

    /**
     * Get the rotation date
     * 
     * @return DateTime|null The rotation date or null if not set
     */
    public function getRotationDate(): ?DateTime;

    /**
     * Get the title for this position
     * 
     * @return string|null The title or null if not available
     */
    public function getTitle(): ?string;

    /**
     * Get the email address for this position
     * 
     * @return string|null The email or null if not available
     */
    public function getPositionEmail(): ?string;

    /**
     * Get the public display name for the member either anonymous name or empty string  
     * 
     * @return string|null Name to display on front end.
     */
    public function getPublicDisplayName(): ?string;

    /**
     * Get the private email address of the member only display in admin
     * 
     * @return string|null Email to display on admin.
     */
    public function getPrivateEmail(): ?string;

    /**
     * Get the private mobile number of the member only display in admin
     * 
     * @return string|null TsmlContact to display on admin.
     */
    public function getPrivateContact(): ?string;

    /**
     * Get the description for this position
     * 
     * @return string|null The description or null if not available
     */
    public function getDescription(): ?string;
}
