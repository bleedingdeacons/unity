<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

/**
 * Member Interface
 */
interface MemberInterface
{
    public function getId(): int;
    public function getAnonymousName(): string;
    public function getPrivateName(): string;
    public function getEmail(): string;
    public function showAnonymousName(): bool;
    public function showMemberProfile(): bool;
    public function getAnonymousProfile(): string;
    public function getIntergroupPosition(): int;
    public function getIntergroupPositionRotation(): string;
    public function getHomeGroup(): mixed;
    public function isGSR(): bool;
    public function getMeetingPO(): mixed;
    public function getPersonalEmail(): string;
    public function getMobileNumber(): string;
}
