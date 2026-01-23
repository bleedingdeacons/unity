<?php

namespace Unity\Meetings;

use Unity\Contact\Interfaces\ContactFactoryInterface;
use Unity\Core\DependencyNotRegisteredException;
use Unity\Locations\Interfaces\LocationRepositoryInterface;
use Unity\Meetings\Interfaces\MeetingFactoryInterface;
use Unity\Meetings\Interfaces\MeetingInterface;

class MeetingFactory implements Interfaces\MeetingFactoryInterface
{
    public function __construct(
        ?ContactFactoryInterface $contactFactory = null,
        ?LocationRepositoryInterface $locationRepository = null
    ) {
    }
    /**
     * @inheritDoc
     * @throws DependencyNotRegisteredException
     */
    public function createFromSource(array $source): ?MeetingInterface
    {
        throw new DependencyNotRegisteredException(MeetingFactoryInterface::class);
    }
}