<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Member View Factory
 */
interface MemberViewFactory
{
    /**
     * Create member views from a list of source IDs
     *
     * IDs that do not resolve to a valid member are silently skipped, so
     * the returned array may be shorter than the input. Order of the
     * returned views matches the order of the input IDs.
     *
     * @param array<int, int> $sourceIds WordPress post IDs to hydrate
     * @return array<int, MemberView> Hydrated views; empty array if none resolve
     */
    public function createFromSource(array $sourceIds): array;
}
