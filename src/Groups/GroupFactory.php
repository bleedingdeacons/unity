<?php

declare(strict_types=1);

namespace Unity\Groups;

use Unity\Core\DummyImplementationException;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupInterface;
use function get_fields;
use function get_permalink;
use function get_post;

/**
 * Concrete Group Factory class
 */
class GroupFactory implements GroupFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFromSource(int $sourceId): ?GroupInterface
    {
        throw new DummyImplementationException(GroupFactoryInterface::class);
    }
}
