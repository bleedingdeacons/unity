<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Core\DummyImplementationException;
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
     * @throws DummyImplementationException
     */
    public function find(int $id): ?MemberInterface
    {
        throw new DummyImplementationException(MemberRepositoryInterface::class);
    }

    /**
     * Find all members with optional filtering
     *
     * @param array $args Optional get_posts arguments
     * @return array Array of MemberInterface objects
     * @throws DummyImplementationException
     */
    public function findAll(array $args = []): array
    {
        throw new DummyImplementationException(MemberRepositoryInterface::class);
    }

    /**
     * Get total count of members matching criteria
     *
     * @param array $args Query arguments
     * @return int Total count
     * @throws DummyImplementationException
     */
    public function count(array $args = []): int
    {
        throw new DummyImplementationException(MemberRepositoryInterface::class);

    }

    /**
     * Save member data
     *
     * @param MemberInterface $member
     * @return bool
     * @throws DummyImplementationException
     */
    public function save(MemberInterface $member): bool
    {
        throw new DummyImplementationException(MemberRepositoryInterface::class);
    }

    /**
     * Delete a member
     *
     * @param int $id
     * @return bool
     * @throws DummyImplementationException
     */
    public function delete(int $id): bool
    {
        throw new DummyImplementationException(MemberRepositoryInterface::class);
    }
}