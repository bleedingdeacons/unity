<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Groups;

use PHPUnit\Framework\TestCase;
use Unity\Groups\Group;
use Unity\Groups\Interfaces\GroupInterface;

/**
 * Tests for Group entity
 */
class GroupTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_with_default_values(): void
    {
        $group = new Group();

        $this->assertEquals(0, $group->getId());
        $this->assertEquals('', $group->getTitle());
        $this->assertEquals('', $group->getEmail());
        $this->assertEquals([], $group->getMeetingIds());
        $this->assertEquals('', $group->getLink());
        $this->assertEquals('', $group->getGroupNotes());
        $this->assertEquals('', $group->getWebsite());
        $this->assertEquals('', $group->getPhone());
        $this->assertEquals('', $group->getVenmo());
        $this->assertEquals('', $group->getPaypal());
        $this->assertEquals('', $group->getSquare());
        $this->assertNull($group->getDistrictId());
        $this->assertNull($group->getLastContact());
        $this->assertEquals([], $group->getContacts());
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_all_values(): void
    {
        $contacts = [
            ['name' => 'John', 'email' => 'john@example.com', 'phone' => '555-1234'],
            ['name' => 'Jane', 'email' => 'jane@example.com', 'phone' => '555-5678'],
        ];

        $group = new Group(
            id: 123,
            title: 'Test Group',
            email: 'group@example.com',
            meetingIds: [1, 2, 3],
            link: 'https://example.com/group/test-group',
            groupNotes: 'Group history and notes',
            website: 'https://testgroup.org',
            phone: '555-GROUP',
            venmo: '@TestGroup',
            paypal: 'TestGroupAA',
            square: '$TestGroup',
            districtId: 42,
            lastContact: '2024-01-15',
            contacts: $contacts
        );

        $this->assertEquals(123, $group->getId());
        $this->assertEquals('Test Group', $group->getTitle());
        $this->assertEquals('group@example.com', $group->getEmail());
        $this->assertEquals([1, 2, 3], $group->getMeetingIds());
        $this->assertEquals('https://example.com/group/test-group', $group->getLink());
        $this->assertEquals('Group history and notes', $group->getGroupNotes());
        $this->assertEquals('https://testgroup.org', $group->getWebsite());
        $this->assertEquals('555-GROUP', $group->getPhone());
        $this->assertEquals('@TestGroup', $group->getVenmo());
        $this->assertEquals('TestGroupAA', $group->getPaypal());
        $this->assertEquals('$TestGroup', $group->getSquare());
        $this->assertEquals(42, $group->getDistrictId());
        $this->assertEquals('2024-01-15', $group->getLastContact());
        $this->assertEquals($contacts, $group->getContacts());
    }

    /**
     * @test
     */
    public function it_is_valid_when_has_id_and_title(): void
    {
        $group = new Group(
            id: 1,
            title: 'Valid Group'
        );

        $this->assertTrue($group->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_id_is_zero(): void
    {
        $group = new Group(
            id: 0,
            title: 'Group'
        );

        $this->assertFalse($group->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_title_is_empty(): void
    {
        $group = new Group(
            id: 1,
            title: ''
        );

        $this->assertFalse($group->isValid());
    }

    /**
     * @test
     */
    public function it_is_valid_without_meetings(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group Without Meetings',
            meetingIds: []
        );

        $this->assertTrue($group->isValid());
    }

    /**
     * @test
     */
    public function it_is_valid_with_multiple_meetings(): void
    {
        $group = new Group(
            id: 5,
            title: 'Multi-Meeting Group',
            meetingIds: [10, 20, 30, 40]
        );

        $this->assertTrue($group->isValid());
        $this->assertCount(4, $group->getMeetingIds());
    }

    /**
     * @test
     */
    public function it_does_not_require_email_for_validity(): void
    {
        $groupWithEmail = new Group(
            id: 1,
            title: 'Group',
            email: 'email@example.com'
        );

        $groupWithoutEmail = new Group(
            id: 1,
            title: 'Group',
            email: ''
        );

        $this->assertTrue($groupWithEmail->isValid());
        $this->assertTrue($groupWithoutEmail->isValid());
    }

    /**
     * @test
     */
    public function it_does_not_require_link_for_validity(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group',
            link: ''
        );

        $this->assertTrue($group->isValid());
    }

    /**
     * @test
     */
    public function hasContributionOptions_returns_true_when_venmo_set(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group',
            venmo: '@GroupVenmo'
        );

        $this->assertTrue($group->hasContributionOptions());
    }

    /**
     * @test
     */
    public function hasContributionOptions_returns_true_when_paypal_set(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group',
            paypal: 'GroupPaypal'
        );

        $this->assertTrue($group->hasContributionOptions());
    }

    /**
     * @test
     */
    public function hasContributionOptions_returns_true_when_square_set(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group',
            square: '$GroupSquare'
        );

        $this->assertTrue($group->hasContributionOptions());
    }

    /**
     * @test
     */
    public function hasContributionOptions_returns_false_when_none_set(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group'
        );

        $this->assertFalse($group->hasContributionOptions());
    }

    /**
     * @test
     */
    public function hasContributionOptions_returns_true_when_multiple_set(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group',
            venmo: '@GroupVenmo',
            paypal: 'GroupPaypal',
            square: '$GroupSquare'
        );

        $this->assertTrue($group->hasContributionOptions());
    }

    /**
     * @test
     */
    public function it_implements_group_interface(): void
    {
        $group = new Group();
        
        $this->assertInstanceOf(GroupInterface::class, $group);
    }
}
