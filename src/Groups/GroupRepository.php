<?php

declare(strict_types=1);

namespace Unity\Groups;

use Unity\Core\DummyImplementationException;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupInterface;
use Unity\Groups\Interfaces\GroupRepositoryInterface;
use Exception;
use function get_posts;
use function is_wp_error;
use function update_field;
use function wp_insert_post;
use function wp_parse_args;
use function wp_update_post;

/**
 * Concrete Group Repository class
 */
class GroupRepository implements GroupRepositoryInterface
{
    private GroupFactoryInterface $factory;
    
    /**
     * GroupRepository constructor
     * 
     * @param GroupFactoryInterface $factory The group factory
     */
    public function __construct(GroupFactoryInterface $factory)
    {
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findById(int $id): ?GroupInterface
    {
        throw new DummyImplementationException(GroupRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findAll(array $args = []): array
    {
        throw new DummyImplementationException(GroupRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function save(GroupInterface $group): bool
    {
        throw new DummyImplementationException(GroupRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function update(GroupInterface $group): bool
    {
        throw new DummyImplementationException(GroupRepositoryInterface::class);
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        throw new Exception('Delete is not implemented');
    }
}
