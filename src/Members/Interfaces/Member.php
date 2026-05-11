<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member Interface
 */
interface Member
{
    public function getId(): int;
    public function getAnonymousName(): string;
    public function showAnonymousName(): bool;
    public function showMemberProfile(): bool;
    public function getAnonymousProfile(): string;
    public function getIntergroupPosition(): int;
    public function getIntergroupPositionRotation(): string;
    public function getHomeGroup(): int;
    public function isGSR(): bool;
    public function getMeetingPO(): mixed;
    public function getPersonalEmail(): string;
    public function getMobileNumber(): string;

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
     * Forms of contact the member accepts for 12th-step calls.
     *
     * Backed by an ACF checkbox field; returned as a list of the
     * selected option values (empty array when nothing is selected).
     *
     * @return array<int, string>
     */
    public function getAccepts(): array;

    /**
     * GDPR: whether the member has accepted the privacy policy
     *
     * @return bool
     */
    public function isGdprAccepted(): bool;

    /**
     * GDPR: timestamp of acceptance, normalised to ISO 8601 (Y-m-d H:i:s)
     *
     * Empty string when the member has never accepted.
     *
     * @return string
     */
    public function getGdprAcceptedAt(): string;

    /**
     * GDPR: version of the privacy policy that was accepted
     *
     * @return string
     */
    public function getGdprAcceptanceVersion(): string;

    /**
     * GDPR: how acceptance was captured (e.g. "web-form", "import", "manual")
     *
     * @return string
     */
    public function getGdprAcceptanceMethod(): string;

    /**
     * GDPR: the exact statement the member accepted
     *
     * @return string
     */
    public function getGdprAcceptanceStatement(): string;

    /**
     * Get the last updated timestamp
     *
     * @return string Last updated datetime string
     */
    public function getUpdated(): string;
}