<?php

declare(strict_types=1);

namespace Unity\Locations\Interfaces;

/**
 * Interface for Locations Factory
 */
interface LocationFactory
{
    /**
     * Create a location from a source ID
     *
     * @param int $sourceId The WordPress post ID as source
     * @return Location|null The created location or null if not found/invalid
     */
    public function createFromSource(int $sourceId): ?Location;
}
