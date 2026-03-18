<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Group View Factory
 */
interface GroupViewFactory
{
    /**
     * Create a group view from a source ID
     * 
     * @param int $sourceId The WordPress post ID as source
     * @return GroupView|null The created group view or null if not found/invalid
     */
    public function createFrom(int $sourceId): ?GroupView;
}
