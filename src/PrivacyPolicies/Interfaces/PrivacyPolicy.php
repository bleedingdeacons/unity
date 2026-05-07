<?php

declare(strict_types=1);

namespace Unity\PrivacyPolicies\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy Interface
 *
 * Defines the contract for a single GDPR privacy policy. The concrete
 * implementation is backed by the `privacy-policy` post type and the
 * "Gdpr" ACF field group, but consumers should depend on this interface
 * so the storage layer can be swapped without breaking callers.
 *
 * The "active" flag distinguishes the policy currently in force from
 * historical revisions; only one policy is expected to be active at a
 * time, but enforcement of that invariant lives in the repository.
 */
interface PrivacyPolicy
{
    /**
     * Get the WordPress post ID
     *
     * @return int Post ID, or 0 for an unsaved policy
     */
    public function getId(): int;

    /**
     * Get the policy title
     *
     * Backed by the WordPress post_title (the post type supports 'title').
     *
     * @return string Policy title
     */
    public function getTitle(): string;

    /**
     * Get the policy body
     *
     * The full policy text as authored in the WYSIWYG editor. May contain
     * HTML markup; callers that render this to non-HTML contexts must
     * sanitise or strip tags themselves.
     *
     * @return string Policy body (HTML)
     */
    public function getPolicy(): string;

    /**
     * Get the policy version
     *
     * Free-form version identifier (e.g. "1.0", "2026-05"). Stored against
     * member acceptance records via Member::getGdprAcceptanceVersion().
     *
     * @return string Version string
     */
    public function getVersion(): string;

    /**
     * Whether this policy is currently active
     *
     * Only the active policy should be presented to members for acceptance.
     *
     * @return bool True if active
     */
    public function isActive(): bool;

    /**
     * Get the last updated timestamp
     *
     * Returned in WordPress' post_modified_gmt format (Y-m-d H:i:s, UTC),
     * matching the convention used by other Unity entities.
     *
     * @return string Last updated datetime string, '' for unsaved policies
     */
    public function getUpdated(): string;
}
