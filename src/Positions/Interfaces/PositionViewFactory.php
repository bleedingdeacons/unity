<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Position View Factory Interface
 */
interface PositionViewFactory
{
    /**
     * Create a position view from a position ID
     * 
     * @param int $positionId
     * @return PositionView|null
     */
    public function createFrom(int $positionId): ?PositionView;

    /**
     * Create position views for all positions
     * 
     * @param array $args Optional arguments for position query
     * @return array Array of PositionView objects
     */
    public function createAll(array $args = []): array;
}
