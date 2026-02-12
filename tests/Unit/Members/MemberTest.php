<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use PHPUnit\Framework\TestCase;
use Unity\Members\Member;

/**
 * Tests for Member entity
 */
class MemberTest extends TestCase
{
    private $member;

    /**
     * @test
     */
    public function it_can_be_instantiated_with_minimal_values(): void
    {
        $member = new Member(id: 1);

        $this->assertEquals(1, $member->getId());
        $this->assertEquals('', $member->getAnonymousName());
        $this->assertEquals('', $member->getEmail());
        $this->assertFalse($member->showAnonymousName());
        $this->assertFalse($member->showMemberProfile());
        $this->assertEquals('', $member->getAnonymousProfile());
        $this->assertEquals(0, $member->getIntergroupPosition());
        $this->assertEquals('', $member->getIntergroupPositionRotation());
        $this->assertNull($member->getHomeGroup());
        $this->assertFalse($member->isGSR());
        $this->assertNull($member->getMeetingPO());
        $this->assertEquals('', $member->getPersonalEmail());
        $this->assertEquals('', $member->getMobileNumber());
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_all_values(): void
    {
        $member = new Member(
            id: 42,
            anonymousName: 'John D.',
            email: 'john@example.com',
            showAnonymousName: true,
            showMemberProfile: true,
            anonymousProfile: 'A member since 2020',
            intergroupPosition: 5,
            intergroupPositionRotation: '2024-01',
            homeGroup: 100,
            isGSR: true,
            meetingPO: 200,
            personalEmail: 'john.personal@example.com',
            mobileNumber: '+1234567890'
        );

        $this->assertEquals(42, $member->getId());
        $this->assertEquals('John D.', $member->getAnonymousName());
        $this->assertEquals('john@example.com', $member->getEmail());
        $this->assertTrue($member->showAnonymousName());
        $this->assertTrue($member->showMemberProfile());
        $this->assertEquals('A member since 2020', $member->getAnonymousProfile());
        $this->assertEquals(5, $member->getIntergroupPosition());
        $this->assertEquals('2024-01', $member->getIntergroupPositionRotation());
        $this->assertEquals(100, $member->getHomeGroup());
        $this->assertTrue($member->isGSR());
        $this->assertEquals(200, $member->getMeetingPO());
        $this->assertEquals('john.personal@example.com', $member->getPersonalEmail());
        $this->assertEquals('+1234567890', $member->getMobileNumber());
    }

    /**
     * @test
     */
    public function it_can_have_home_group_as_array(): void
    {
        $homeGroups = [100, 200, 300];
        $member = new Member(
            id: 1,
            homeGroup: $homeGroups
        );

        $this->assertEquals($homeGroups, $member->getHomeGroup());
    }

    /**
     * @test
     */
    public function it_can_have_meeting_po_as_array(): void
    {
        $meetings = [10, 20];
        $member = new Member(
            id: 1,
            meetingPO: $meetings
        );

        $this->assertEquals($meetings, $member->getMeetingPO());
    }

    /**
     * @test
     */
    public function gsr_flag_can_be_toggled(): void
    {
        $gsrMember = new Member(id: 1, isGSR: true);
        $regularMember = new Member(id: 2, isGSR: false);

        $this->assertTrue($gsrMember->isGSR());
        $this->assertFalse($regularMember->isGSR());
    }

    /**
     * @test
     */
    public function visibility_flags_work_independently(): void
    {
        $member1 = new Member(
            id: 1,
            showAnonymousName: true,
            showMemberProfile: false
        );

        $member2 = new Member(
            id: 2,
            showAnonymousName: false,
            showMemberProfile: true
        );

        $this->assertTrue($member1->showAnonymousName());
        $this->assertFalse($member1->showMemberProfile());

        $this->assertFalse($member2->showAnonymousName());
        $this->assertTrue($member2->showMemberProfile());
    }

    /**
     * @test
     */
    public function it_handles_empty_strings_for_optional_fields(): void
    {
        $this->member = new Member(
            id: 1,
            anonymousName: '',
            email: '',
            personalEmail: '',
            mobileNumber: ''
        );
        $member = $this->member;

        $this->assertEmpty($member->getAnonymousName());
        $this->assertEmpty($member->getEmail());
        $this->assertEmpty($member->getPersonalEmail());
        $this->assertEmpty($member->getMobileNumber());
    }

    /**
     * @test
     */
    public function it_stores_intergroup_position_as_integer(): void
    {
        $member = new Member(
            id: 1,
            intergroupPosition: 10
        );

        $this->assertIsInt($member->getIntergroupPosition());
        $this->assertEquals(10, $member->getIntergroupPosition());
    }
}
