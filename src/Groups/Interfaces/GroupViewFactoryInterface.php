<?php

declare(strict_types=1);

namespace Unity\Groups\Interfaces;

/**
 * Interface for Group View Factory
 */
interface GroupViewFactoryInterface
{
    /**
     * Create a group view from a source ID
     * 
     * @param int $sourceId The WordPress post ID as source
     * @return GroupViewInterface|null The created group view or null if not found/invalid
     */
    public function createFrom(int $sourceId): ?GroupViewInterface;
}
