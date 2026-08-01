<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Members\Interfaces\Member;
use Unity\Members\ResponderCertification;

/**
 * An inert Member value object for tests.
 *
 * Unity ships no concrete Member — only the interface — so every consuming
 * plugin that wanted a plain, predictable Member (rather than a mock carrying
 * expectations) wrote its own. There were eleven of them across the suite:
 * two named fixture classes and nine anonymous classes inlined in test files,
 * most of which existed only to fill in the fourteen-odd accessors the test
 * did not care about.
 *
 * Shipping it from Unity rather than from a separate test package is what
 * makes drift impossible: this class implements the contract that lives three
 * directories away, so adding a method to Member breaks Unity's own build
 * before any consumer sees it.
 *
 * Every field is defaulted, so a test names only what it cares about:
 *
 *     new MemberStub(id: 7, telephoneResponder: true, anonymousName: 'Alice')
 *
 * Not final: a plugin whose members are all of one flavour can subclass this
 * to fix its own defaults in one place rather than repeating them at every
 * call site — see Trusted\Tests\Fixtures\ResponderStub.
 *
 * @phpstan-consistent-constructor
 */
class MemberStub implements Member
{
    /** @param array<int, string> $accepts */
    public function __construct(
        private int $id = 0,
        private string $anonymousName = '',
        private bool $showAnonymousName = false,
        private bool $showMemberProfile = false,
        private string $anonymousProfile = '',
        private int $intergroupPosition = 0,
        private string $intergroupPositionRotation = '',
        private int $homeGroup = 0,
        private bool $isGSR = false,
        private mixed $meetingPO = null,
        private string $personalEmail = '',
        private string $mobileNumber = '',
        private bool $twelfthStepper = false,
        private bool $telephoneResponder = false,
        private ResponderCertification $responderCertification = ResponderCertification::None,
        private string $area = '',
        private array $accepts = [],
        private bool $gdprAccepted = false,
        private string $gdprAcceptedAt = '',
        private string $gdprAcceptanceVersion = '',
        private string $gdprAcceptanceMethod = '',
        private string $gdprAcceptanceStatement = '',
        private string $updated = ''
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnonymousName(): string
    {
        return $this->anonymousName;
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

    public function getHomeGroup(): int
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

    public function isTwelfthStepper(): bool
    {
        return $this->twelfthStepper;
    }

    public function isTelephoneResponder(): bool
    {
        return $this->telephoneResponder;
    }

    public function getResponderCertification(): ResponderCertification
    {
        return $this->responderCertification;
    }

    public function getArea(): string
    {
        return $this->area;
    }

    /** @return array<int, string> */
    public function getAccepts(): array
    {
        return $this->accepts;
    }

    public function isGdprAccepted(): bool
    {
        return $this->gdprAccepted;
    }

    public function getGdprAcceptedAt(): string
    {
        return $this->gdprAcceptedAt;
    }

    public function getGdprAcceptanceVersion(): string
    {
        return $this->gdprAcceptanceVersion;
    }

    public function getGdprAcceptanceMethod(): string
    {
        return $this->gdprAcceptanceMethod;
    }

    public function getGdprAcceptanceStatement(): string
    {
        return $this->gdprAcceptanceStatement;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }
}
