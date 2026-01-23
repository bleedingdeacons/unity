<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Core\DependencyNotRegisteredException;
use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberInterface;

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
     * @throws DependencyNotRegisteredException
     */
    public function createFromSource(int $id): MemberInterface
    {
        throw new DependencyNotRegisteredException(MemberFactoryInterface::class);
    }
}