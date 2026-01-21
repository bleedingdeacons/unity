<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Groups;

use Mockery;
use Unity\Groups\Group;
use Unity\Groups\GroupFields;
use Unity\Groups\GroupRepository;
use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupInterface;
use Unity\Tests\TestCase;
use WP_Mock;

/**
 * Tests for GroupRepository
 */
class GroupRepositoryTest extends TestCase
{
    private GroupFactoryInterface $factory;
    private GroupRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = Mockery::mock(GroupFactoryInterface::class);
        $this->repository = new GroupRepository($this->factory);
    }

    /**
     * @test
     */
    public function it_finds_group_by_id_using_factory(): void
    {
        $groupId = 100;
        $expectedGroup = new Group(
            id: $groupId,
            title: 'Test Group',
            meetingIds: [1]
        );

        $this->factory
            ->shouldReceive('createFromSource')
            ->once()
            ->with($groupId)
            ->andReturn($expectedGroup);

        $result = $this->repository->findById($groupId);

        $this->assertSame($expectedGroup, $result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_group_not_found(): void
    {
        $this->factory
            ->shouldReceive('createFromSource')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_finds_all_groups(): void
    {
        $posts = [
            $this->createMockPost(['ID' => 1, 'post_type' => GroupFields::GROUP_POST_TYPE]),
            $this->createMockPost(['ID' => 2, 'post_type' => GroupFields::GROUP_POST_TYPE]),
            $this->createMockPost(['ID' => 3, 'post_type' => GroupFields::GROUP_POST_TYPE]),
        ];

        $group1 = new Group(id: 1, title: 'Group 1', meetingIds: [10]);
        $group2 = new Group(id: 2, title: 'Group 2', meetingIds: [20]);
        $group3 = new Group(id: 3, title: 'Group 3', meetingIds: [30]);

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function ($args, $defaults) {
                return array_merge($defaults, $args);
            });

        WP_Mock::userFunction('get_posts')
            ->once()
            ->andReturn($posts);

        $this->factory
            ->shouldReceive('createFromSource')
            ->with(1)
            ->andReturn($group1);

        $this->factory
            ->shouldReceive('createFromSource')
            ->with(2)
            ->andReturn($group2);

        $this->factory
            ->shouldReceive('createFromSource')
            ->with(3)
            ->andReturn($group3);

        $results = $this->repository->findAll();

        $this->assertCount(3, $results);
        $this->assertSame($group1, $results[0]);
        $this->assertSame($group2, $results[1]);
        $this->assertSame($group3, $results[2]);
    }

    /**
     * @test
     */
    public function it_filters_out_null_groups_in_find_all(): void
    {
        $posts = [
            $this->createMockPost(['ID' => 1]),
            $this->createMockPost(['ID' => 2]),
        ];

        $group1 = new Group(id: 1, title: 'Valid Group', meetingIds: [10]);

        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function ($args, $defaults) {
                return array_merge($defaults, $args);
            });

        WP_Mock::userFunction('get_posts')
            ->once()
            ->andReturn($posts);

        $this->factory
            ->shouldReceive('createFromSource')
            ->with(1)
            ->andReturn($group1);

        $this->factory
            ->shouldReceive('createFromSource')
            ->with(2)
            ->andReturn(null); // This one returns null

        $results = $this->repository->findAll();

        $this->assertCount(1, $results);
        $this->assertSame($group1, $results[0]);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_no_groups_found(): void
    {
        WP_Mock::userFunction('wp_parse_args')
            ->once()
            ->andReturnUsing(function ($args, $defaults) {
                return array_merge($defaults, $args);
            });

        WP_Mock::userFunction('get_posts')
            ->once()
            ->andReturn([]);

        $results = $this->repository->findAll();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     */
    public function it_saves_new_group(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(0);
        $group->shouldReceive('isValid')->andReturn(true);
        $group->shouldReceive('getTitle')->andReturn('New Group');
        $group->shouldReceive('getEmail')->andReturn('new@example.com');
        $group->shouldReceive('getMeetingIds')->andReturn([100]);

        WP_Mock::userFunction('wp_insert_post')
            ->once()
            ->andReturn(42); // Returns new post ID

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->with(42)
            ->andReturn(false);

        WP_Mock::userFunction('update_field')
            ->times(3);

        $result = $this->repository->save($group);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_false_when_saving_invalid_new_group(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(0);
        $group->shouldReceive('isValid')->andReturn(false);

        $result = $this->repository->save($group);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_updates_existing_group(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(50);
        $group->shouldReceive('isValid')->andReturn(true);
        $group->shouldReceive('getTitle')->andReturn('Updated Group');
        $group->shouldReceive('getEmail')->andReturn('updated@example.com');
        $group->shouldReceive('getMeetingIds')->andReturn([200]);

        WP_Mock::userFunction('wp_update_post')
            ->once()
            ->andReturn(50);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->with(50)
            ->andReturn(false);

        WP_Mock::userFunction('update_field')
            ->times(3);

        $result = $this->repository->update($group);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_false_when_updating_group_with_zero_id(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(0);

        $result = $this->repository->update($group);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_returns_false_when_updating_invalid_group(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(50);
        $group->shouldReceive('isValid')->andReturn(false);

        $result = $this->repository->update($group);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function delete_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delete is not implemented');

        $this->repository->delete(1);
    }

    /**
     * @test
     */
    public function save_calls_update_for_existing_group(): void
    {
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('getId')->andReturn(100); // Existing group
        $group->shouldReceive('isValid')->andReturn(true);
        $group->shouldReceive('getTitle')->andReturn('Existing Group');
        $group->shouldReceive('getEmail')->andReturn('existing@example.com');
        $group->shouldReceive('getMeetingIds')->andReturn([300]);

        WP_Mock::userFunction('wp_update_post')
            ->once()
            ->andReturn(100);

        WP_Mock::userFunction('is_wp_error')
            ->once()
            ->andReturn(false);

        WP_Mock::userFunction('update_field')
            ->times(3);

        $result = $this->repository->save($group);

        $this->assertTrue($result);
    }
}
