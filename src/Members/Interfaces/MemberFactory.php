<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

/**
 * Member Factory Interface
 */
interface MemberFactory
{
    /**
     * Create a Member from a source ID
     *
     * @param int $id WordPress post ID
     * @return Member
     */
    public function createFromSource(int $id): Member;

    public function createFrom(int $id): Member;
}
