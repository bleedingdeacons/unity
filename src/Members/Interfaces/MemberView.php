<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Groups\Interfaces\GroupView;

/**
 * Interface for Member View
 *
 * Read-only, public-facing projection of a Member. Exposes only the
 * always-displayable fields (anonymous name is always shown) and excludes
 * sensitive or admin-only data (personal email, mobile number, profile text,
 * GDPR metadata, meeting PO) so it is safe to render in directories,
 * rosters, and 12th-step listings.
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
     * Get the intergroup position post ID
     *
     * @return int The intergroup position ID, 0 if none
     */
    public function getIntergroupPosition(): int;

    /**
     * Get the intergroup position rotation date (Y-m-d)
     *
     * @return string The rotation date, empty string if none
     */
    public function getIntergroupPositionRotation(): string;

    /**
     * Get the member's home group as a view
     *
     * @return GroupView|null The home group view, or null if none set
     */
    public function getHomeGroup(): ?GroupView;

    /**
     * Whether the member is a General Service Representative
     *
     * @return bool
     */
    public function isGSR(): bool;

    /**
     * Whether the member is available for 12th-step calls
     *
     * @return bool
     */
    public function isTwelfthStepper(): bool;

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
