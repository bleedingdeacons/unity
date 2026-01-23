<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Core\DependencyNotRegisteredException;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberInterface;
use Unity\Members\Interfaces\MemberRepositoryInterface;
use function get_post;
use function get_posts;
use function update_field;
use function wp_delete_post;

/**
 * Member Repository
 */
class MemberRepository implements MemberRepositoryInterface
{
    private MemberFactoryInterface $memberFactory;

    /**
     * Repository constructor
     *
     * @param MemberFactoryInterface $memberFactory
     */
    public function __construct(MemberFactoryInterface $memberFactory)
    {
    }

    /**
     * Find a member by ID
     *
     * @param int $id
     * @return MemberInterface|null
     * @throws DependencyNotRegisteredException
     */
    public function find(int $id): ?MemberInterface
    {
        throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);
    }

    /**
     * Find all members with optional filtering
     *
     * @param array $args Optional get_posts arguments
     * @return array Array of MemberInterface objects
     * @throws DependencyNotRegisteredException
     */
    public function findAll(array $args = []): array
    {
        throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);
    }

    /**
     * Get total count of members matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     * @throws DependencyNotRegisteredException
     */
    public function count(array $args = []): int
    {
        throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);

    }

    /**
     * Save member data
     *
     * @param MemberInterface $member
     * @return bool
     * @throws DependencyNotRegisteredException
     */
    public function save(MemberInterface $member): bool
    {
        throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);
    }

    /**
     * Delete a member
     *
     * @param int $id
     * @return bool
     * @throws DependencyNotRegisteredException
     */
    public function delete(int $id): bool
    {
        throw new DependencyNotRegisteredException(MemberRepositoryInterface::class);
    }
}