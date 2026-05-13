<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Position Factory
 */
interface PositionFactory
{
    /**
     * Create a position from a source ID
     * 
     * @param int $sourceId The WordPress post ID as source
     * @return Position|null The created position or null if not found/invalid
     */
    public function createFromSource(int $sourceId): ?Position;

    /**
     * Create a new Position from imported data without requiring an existing post
     *
     * Used by Reconcile (and other importers) to build a Position object
     * from raw field values. The post is created first via wp_insert_post,
     * then this method wraps the data as a concrete Position ready for
     * persistence.
     *
     * @param int    $id                Post ID (from wp_insert_post)
     * @param int    $minimumSobriety   Minimum sobriety requirement in months
     * @param int    $termYears         Term length in years
     * @param string $email             Email address
     * @param string $longName          Long name/title
     * @param string $shortDescription  Short description
     * @param string $summary           Summary
     * @return Position
     */
    public function createNew(
        int $id,
        int $minimumSobriety = 6,
        int $termYears = 1,
        string $email = '',
        string $longName = '',
        string $shortDescription = '',
        string $summary = ''
    ): Position;
}
