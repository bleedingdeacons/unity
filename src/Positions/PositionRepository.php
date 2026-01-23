<?php

declare(strict_types=1);

namespace Unity\Positions;

use Unity\Core\DependencyNotRegisteredException;
use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionInterface;
use Unity\Positions\Interfaces\PositionRepositoryInterface;
use Exception;
use function get_posts;
use function is_wp_error;
use function update_field;
use function wp_insert_post;
use function wp_parse_args;
use function wp_update_post;

/**
 * Concrete Position Repository class
 */
class PositionRepository implements PositionRepositoryInterface
{
    private PositionFactoryInterface $factory;

    /**
     * PositionRepository constructor
     *
     * @param PositionFactoryInterface $factory The position factory
     */
    public function __construct(PositionFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function findById(int $id): ?PositionInterface
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function findAll(array $args = []): array
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function count(array $args = []): int
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function save(PositionInterface $position): bool
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function update(PositionInterface $position): bool
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DependencyNotRegisteredException
     */
    public function delete(int $id): bool
    {
        throw new DependencyNotRegisteredException(PositionRepositoryInterface::class);
    }
}