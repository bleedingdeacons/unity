<?php

declare(strict_types=1);

namespace Unity\Locations;

use Unity\Core\DummyImplementationException;
use Unity\Locations\Interfaces\LocationInterface;
use Unity\Locations\Interfaces\LocationRepositoryInterface;
use Exception;
use function get_posts;
use function wp_parse_args;

/**
 * Concrete Locations Repository class
 *
 * Handles retrieval of Locations entities from the WordPress database.
 * Save/update/delete operations are not implemented as locations are
 * typically managed by the TSML plugin.
 */
class LocationRepository implements LocationRepositoryInterface
{
    private LocationFactoryInterface $factory;

    /**
     * The location post type - uses TSML's location post type
     */
    private const LOCATION_POST_TYPE = 'tsml_location';

    /**
     * LocationRepository constructor
     *
     * @param LocationFactoryInterface $factory The location factory
     */
    public function __construct(LocationFactoryInterface $factory)
    {
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findById(int $id): ?LocationInterface
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findAll(array $args = []): array
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findByCity(string $city): array
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findByRegion(string $region): array
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function save(LocationInterface $location): bool
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function update(LocationInterface $location): bool
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function delete(int $id): bool
    {
        throw new DummyImplementationException(LocationRepositoryInterface::class);
    }
}
