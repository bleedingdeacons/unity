<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

/**
 * Interface for Position Factory
 */
interface PositionFactoryInterface
{
    /**
     * Create a position from a source ID
     * 
     * @param int $sourceId The WordPress post ID as source
     * @return PositionInterface|null The created position or null if not found/invalid
     */
    public function createFromSource(int $sourceId): ?PositionInterface;
}
