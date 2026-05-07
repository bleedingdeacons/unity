<?php

declare(strict_types=1);

namespace Unity\PrivacyPolicies\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy Policy Factory Interface
 *
 * Defines the contract for creating PrivacyPolicy objects.
 *
 * Two construction paths are provided to mirror the pattern used by
 * MemberFactory: createFromSource() reads a persisted post by ID,
 * while createNew() builds an in-memory object from raw values
 * (used by importers and the create-then-save flow in the repository).
 */
interface PrivacyPolicyFactory
{
    /**
     * Create a PrivacyPolicy from a WordPress post ID
     *
     * Reads the post and its ACF fields (`gdpr-policy`, `gdpr-policy-version`,
     * `gdpr-policy-active`) and wraps them as a PrivacyPolicy.
     *
     * @param int $id WordPress post ID
     * @return PrivacyPolicy
     */
    public function createFromSource(int $id): PrivacyPolicy;

    /**
     * Create a new PrivacyPolicy from raw field values
     *
     * Builds an object directly without touching the database. Used by
     * the repository's save-after-insert flow and by importers that
     * already have the data in hand.
     *
     * @param int    $id      WordPress post ID (0 for an unsaved policy)
     * @param string $title   Policy title (post_title)
     * @param string $policy  Policy body (HTML from the WYSIWYG field)
     * @param string $version Free-form version identifier
     * @param bool   $active  Whether this policy is currently active
     * @param string $updated Last updated datetime (Y-m-d H:i:s, UTC)
     * @return PrivacyPolicy
     */
    public function createNew(
        int $id,
        string $title = '',
        string $policy = '',
        string $version = '',
        bool $active = false,
        string $updated = ''
    ): PrivacyPolicy;
}
