<?php

declare(strict_types=1);

namespace Unity\Meetings;

use Unity\Common\Interfaces\CacheInterface;
use Unity\Core\DummyImplementationException;
use Unity\Meetings\Interfaces\MeetingFactoryInterface;
use Unity\Meetings\Interfaces\MeetingInterface;
use Unity\Meetings\Interfaces\MeetingRepositoryInterface;

/**
 * Class MeetingRepository
 *
 * Repository for retrieving Meeting objects from WordPress.
 */
class MeetingRepository implements MeetingRepositoryInterface
{
    private const POST_TYPE = 'tsml_meeting';
    private const CACHE_GROUP = 'unity_meetings';
    private const CACHE_TTL = 3600; // 1 hour

    private MeetingFactoryInterface $factory;
    private ?CacheInterface $cache;

    /**
     * MeetingRepository constructor.
     *
     * @param MeetingFactoryInterface $factory Meeting factory
     * @param CacheInterface|null $cache Optional cache implementation
     */
    public function __construct(
        MeetingFactoryInterface $factory,
        ?CacheInterface $cache = null
    ) {
        $this->factory = $factory;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function find(int $id): ?MeetingInterface
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findAll(array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findByDay(int $day, array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findOnline(array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findInPerson(array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findByGroupId(int $groupId, array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function findByLocationId(int $locationId, array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function search(string $keyword, array $args = []): array
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws DummyImplementationException
     */
    public function count(array $args = []): int
    {
        throw new DummyImplementationException(MeetingRepositoryInterface::class);
    }

}