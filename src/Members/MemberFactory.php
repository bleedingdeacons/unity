<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Core\DummyImplementationException;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberInterface;
use function get_field;
use function get_the_title;

/**
 * Member Factory Implementation
 */
class MemberFactory implements MemberFactoryInterface
{
    /**
     * Create a new Member instance from a WordPress post ID
     *
     * @param int $id WordPress post ID
     * @return MemberInterface
     * @throws DummyImplementationException
     */
    public function createFromSource(int $id): MemberInterface
    {
        throw new DummyImplementationException(MemberFactoryInterface::class);
    }
}