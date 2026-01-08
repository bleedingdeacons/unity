<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Members\Interfaces\MemberInterface;

/**
 * Member Class
 */
class Member implements MemberInterface
{
    private int $id;
    private string $anonymousName;
    private string $privateName;
    private string $email;
    private bool $showAnonymousName;
    private bool $showMemberProfile;
    private string $anonymousProfile;
    private int $intergroupPosition;
    private string $intergroupPositionRotation;
    private mixed $homeGroup;
    private bool $isGSR;
    private mixed $meetingPO;
    private string $personalEmail;
    private string $mobileNumber;

    /**
     * Member constructor
     * 
     * @param int $id Post ID
     * @param string $anonymousName Anonymous name
     * @param string $privateName Private name
     * @param string $email Email address
     * @param bool $showAnonymousName Show anonymous name flag
     * @param bool $showMemberProfile Show member profile flag
     * @param string $anonymousProfile Anonymous profile text
     * @param int $intergroupPosition Intergroup position ID
     * @param string $intergroupPositionRotation Intergroup position rotation info
     * @param mixed $homeGroup Home group reference
     * @param bool $isGSR GSR flag
     * @param mixed $meetingPO Meeting PO reference
     * @param string $personalEmail Personal email address
     * @param string $mobileNumber Mobile phone number
     */
    public function __construct(
        int $id,
        string $anonymousName = '',
        string $privateName = '',
        string $email = '',
        bool $showAnonymousName = false,
        bool $showMemberProfile = false,
        string $anonymousProfile = '',
        int $intergroupPosition = 0,
        string $intergroupPositionRotation = '',
        mixed $homeGroup = null,
        bool $isGSR = false,
        mixed $meetingPO = null,
        string $personalEmail = '',
        string $mobileNumber = ''
    ) {
        $this->id = $id;
        $this->anonymousName = $anonymousName;
        $this->privateName = $privateName;
        $this->email = $email;
        $this->showAnonymousName = $showAnonymousName;
        $this->showMemberProfile = $showMemberProfile;
        $this->anonymousProfile = $anonymousProfile;
        $this->intergroupPosition = $intergroupPosition;
        $this->intergroupPositionRotation = $intergroupPositionRotation;
        $this->homeGroup = $homeGroup;
        $this->isGSR = $isGSR;
        $this->meetingPO = $meetingPO;
        $this->personalEmail = $personalEmail;
        $this->mobileNumber = $mobileNumber;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnonymousName(): string
    {
        return $this->anonymousName;
    }

    public function getPrivateName(): string
    {
        return $this->privateName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function showAnonymousName(): bool
    {
        return $this->showAnonymousName;
    }

    public function showMemberProfile(): bool
    {
        return $this->showMemberProfile;
    }

    public function getAnonymousProfile(): string
    {
        return $this->anonymousProfile;
    }

    public function getIntergroupPosition(): int
    {
        return $this->intergroupPosition;
    }

    public function getIntergroupPositionRotation(): string
    {
        return $this->intergroupPositionRotation;
    }

    public function getHomeGroup(): mixed
    {
        return $this->homeGroup;
    }

    public function isGSR(): bool
    {
        return $this->isGSR;
    }

    public function getMeetingPO(): mixed
    {
        return $this->meetingPO;
    }

    public function getPersonalEmail(): string
    {
        return $this->personalEmail;
    }

    public function getMobileNumber(): string
    {
        return $this->mobileNumber;
    }
}
