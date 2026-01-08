<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Groups;

use PHPUnit\Framework\TestCase;
use Unity\Groups\Group;

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
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_values(): void
    {
        $group = new Group(
            id: 123,
            title: 'Test Group',
            email: 'test@example.com',
            meetingIds: [1, 2, 3],
            link: 'https://example.com/group/test-group'
        );

        $this->assertEquals(123, $group->getId());
        $this->assertEquals('Test Group', $group->getTitle());
        $this->assertEquals('test@example.com', $group->getEmail());
        $this->assertEquals([1, 2, 3], $group->getMeetingIds());
        $this->assertEquals('https://example.com/group/test-group', $group->getLink());
    }

    /**
     * @test
     */
    public function it_is_valid_when_has_id_title_and_at_least_one_meeting(): void
    {
        $group = new Group(
            id: 1,
            title: 'Valid Group',
            email: '',
            meetingIds: [100]
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
            title: 'Group',
            meetingIds: [1]
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
            title: '',
            meetingIds: [1]
        );

        $this->assertFalse($group->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_has_no_meetings(): void
    {
        $group = new Group(
            id: 1,
            title: 'Group Without Meetings',
            meetingIds: []
        );

        $this->assertFalse($group->isValid());
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
            email: 'email@example.com',
            meetingIds: [1]
        );

        $groupWithoutEmail = new Group(
            id: 1,
            title: 'Group',
            email: '',
            meetingIds: [1]
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
            meetingIds: [1],
            link: ''
        );

        $this->assertTrue($group->isValid());
    }
}
