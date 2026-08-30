<?php

declare(strict_types=1);

namespace Unity\Committees\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Committee Factory
 */
interface CommitteeFactory
{
    /**
     * Create a committee from a source ID
     *
     * @param int $sourceId The WordPress term ID as source
     * @return Committee|null The created committee, or null when the term does
     *                        not exist or belongs to another taxonomy
     */
    public function createFromSource(int $sourceId): ?Committee;
}
