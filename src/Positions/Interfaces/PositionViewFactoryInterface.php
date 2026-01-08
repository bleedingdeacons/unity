<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

/**
 * Position View Factory Interface
 */
interface PositionViewFactoryInterface
{
    /**
     * Create a position view from a position ID
     * 
     * @param int $positionId
     * @return PositionViewInterface|null
     */
    public function createFrom(int $positionId): ?PositionViewInterface;

    /**
     * Create position views for all positions
     * 
     * @param array $args Optional arguments for position query
     * @return array Array of PositionViewInterface objects
     */
    public function createAll(array $args = []): array;
}
