<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

/**
 * Member Factory Interface
 */
interface MemberFactoryInterface
{
    /**
     * Create a Member from a source ID
     *
     * @param int $id WordPress post ID
     * @return MemberInterface
     */
    public function createFromSource(int $id): MemberInterface;
}
