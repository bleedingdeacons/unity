<?php

declare(strict_types=1);

namespace Unity\Locations;

use Unity\Core\DummyImplementationException;
use Unity\Locations\Interfaces\LocationFactoryInterface;
use Unity\Locations\Interfaces\LocationInterface;
use function get_permalink;
use function get_post;
use function get_post_meta;
use function wp_get_post_terms;

/**
 * Concrete Locations Factory class
 *
 * Creates Locations objects from WordPress post IDs, typically
 * from the TSML plugin's tsml_location post type.
 */
class LocationFactory implements LocationFactoryInterface
{
    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function createFromSource(int $sourceId): ?LocationInterface
    {
        throw new DummyImplementationException(LocationFactoryInterface::class);
    }

}
