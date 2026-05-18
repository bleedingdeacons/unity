<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Member View
 *
 * Read-only projection of a Member with flat accessors for the fields
 * most often needed when rendering rosters, directories, and 12th-step
 * listings. Home group and intergroup position are exposed as
 * (id, name) pairs rather than nested objects to keep the view easy
 * to serialise and template against.
 */
interface MemberView
{
    /**
     * Get the ID of the member
     *
     * @return int The member ID
     */
    public function getId(): int;

    /**
     * Get the anonymous name of the member (e.g. "John D.")
     *
     * @return string The anonymous name
     */
    public function getAnonymousName(): string;

    /**
     * Get the member's personal email address
     *
     * @return string The personal email, empty string if none
     */
    public function getPersonalEmail(): string;

    /**
     * Get the member's mobile phone number
     *
     * @return string The mobile number, empty string if none
     */
    public function getMobileNumber(): string;

    /**
     * Get the home group ID
     *
     * @return int The home group post ID, 0 if none
     */
    public function getHomeGroupId(): int;

    /**
     * Get the home group name
     *
     * @return string The home group name, empty string if none
     */
    public function getHomeGroupName(): string;

    /**
     * Whether the member has a home group set
     *
     * @return bool
     */
    public function hasHomeGroup(): bool;

    /**
     * Whether the member is a General Service Representative for their home group
     *
     * @return bool
     */
    public function isGSR(): bool;

    /**
     * Get the intergroup position ID
     *
     * @return int The position post ID, 0 if none held
     */
    public function getPositionId(): int;

    /**
     * Get the intergroup position name
     *
     * @return string The position name, empty string if none held
     */
    public function getPositionName(): string;

    /**
     * Whether the member currently holds an intergroup position
     *
     * @return bool
     */
    public function hasPosition(): bool;

    /**
     * Get the rotation date for the held intergroup position
     *
     * @return string The rotation date in Y-m-d format, empty string if none
     */
    public function getRotationDate(): string;

    /**
     * Whether the member is available for 12th-step calls
     *
     * @return bool
     */
    public function isTwelfthStepper(): bool;

    /**
     * Whether the member is available as a telephone responder
     *
     * @return bool
     */
    public function isTelephoneResponder(): bool;

    /**
     * Geographic area the member covers for 12th-step calls
     *
     * @return string
     */
    public function getArea(): string;

    /**
     * Forms of contact the member accepts for 12th-step calls
     *
     * @return array<int, string> Selected option values; empty when none
     */
    public function getAccepts(): array;
}
