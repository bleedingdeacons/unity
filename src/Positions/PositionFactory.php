<?php

declare(strict_types=1);

namespace Unity\Positions;

use Unity\Core\DummyImplementationException;
use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionInterface;
use function get_fields;
use function get_permalink;
use function get_post;

/**
 * Concrete Position Factory class
 */
class PositionFactory implements PositionFactoryInterface
{
    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function createFromSource(int $sourceId): ?PositionInterface
    {
        throw new DummyImplementationException(PositionFactoryInterface::class);
    }
}
