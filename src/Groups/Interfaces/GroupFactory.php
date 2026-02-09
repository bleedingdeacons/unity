<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

/**
 * Interface for Group Factory
 */
interface GroupFactory
{
    /**
     * Create a group from a source ID
     * 
     * @param int $sourceId The WordPress post ID as source
     * @return Group|null The created group or null if not found/invalid
     */
    public function createFromSource(int $sourceId): ?Group;
}
