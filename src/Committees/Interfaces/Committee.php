<?php

declare(strict_types=1);

namespace Unity\Committees\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Committee entity
 *
 * A committee is a node in the intergroup's committee hierarchy — backed by a
 * hierarchical taxonomy term rather than a post, which is why this carries no
 * `getUpdated()`: terms have no modified date.
 *
 * A committee knows its own parent but not its children. Walking the tree is
 * {@see CommitteeRepository}'s job, so that a Committee handed around by a
 * caller is always fully hydrated rather than a partial branch whose children
 * may or may not have been loaded.
 */
interface Committee
{
    /**
     * Get the ID of the committee
     *
     * This is a term ID, and it differs between environments: the tree is
     * maintained by hand in wp-admin on each site, so dev, test and production
     * assign their own IDs to the same committee. Anything persisted or
     * written into code should key on {@see getSlug()} instead.
     *
     * @return int The committee's term ID
     */
    public function getId(): int;

    /**
     * Get the committee's slug
     *
     * The stable identifier across environments, and therefore the one to
     * reference from code. Renaming a committee is free; changing its slug is
     * a breaking change.
     *
     * @return string The slug
     */
    public function getSlug(): string;

    /**
     * Get the committee's display name
     *
     * @return string The name
     */
    public function getName(): string;

    /**
     * Get the committee's description
     *
     * @return string The description, or an empty string when unset
     */
    public function getDescription(): string;

    /**
     * Get the ID of this committee's parent
     *
     * @return int The parent's term ID, or 0 when this is a root committee
     */
    public function getParentId(): int;

    /**
     * Whether this committee sits at the top of the tree
     *
     * @return bool True when the committee has no parent
     */
    public function isRoot(): bool;
}
