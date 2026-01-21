<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Groups;

use Unity\Groups\Group;
use Unity\Groups\GroupFactory;
use Unity\Groups\GroupFields;
use Unity\Tests\TestCase;
use WP_Mock;

/**
 * Tests for GroupFactory
 */
class GroupFactoryTest extends TestCase
{
    private GroupFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new GroupFactory();
    }

    /**
     * @test
     */
    public function it_returns_null_when_post_does_not_exist(): void
    {
        WP_Mock::userFunction('get_post')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->factory->createFromSource(999);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_post_is_wrong_type(): void
    {
        $post = $this->createMockPost([
            'ID' => 123,
            'post_type' => 'post', // Wrong type, should be 'home-group'
        ]);

        WP_Mock::userFunction('get_post')
            ->once()
            ->with(123)
            ->andReturn($post);

        $result = $this->factory->createFromSource(123);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_creates_group_from_valid_post(): void
    {
        $postId = 100;
        $post = $this->createMockPost([
            'ID' => $postId,
            'post_type' => GroupFields::GROUP_POST_TYPE,
        ]);

        $acfData = [
            GroupFields::TITLE => 'Test Group',
            GroupFields::GENERIC_EMAIL => 'group@example.com',
            GroupFields::MEETING => [10, 20],
        ];

        WP_Mock::userFunction('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        WP_Mock::userFunction('get_fields')
            ->once()
            ->with($postId)
            ->andReturn($acfData);

        WP_Mock::userFunction('get_permalink')
            ->once()
            ->with($postId)
            ->andReturn('https://example.com/group/test-group');

        $result = $this->factory->createFromSource($postId);

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals($postId, $result->getId());
        $this->assertEquals('Test Group', $result->getTitle());
        $this->assertEquals('group@example.com', $result->getEmail());
        $this->assertEquals([10, 20], $result->getMeetingIds());
        $this->assertEquals('https://example.com/group/test-group', $result->getLink());
    }

    /**
     * @test
     */
    public function it_handles_empty_acf_fields(): void
    {
        $postId = 200;
        $post = $this->createMockPost([
            'ID' => $postId,
            'post_type' => GroupFields::GROUP_POST_TYPE,
        ]);

        WP_Mock::userFunction('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        WP_Mock::userFunction('get_fields')
            ->once()
            ->with($postId)
            ->andReturn(false); // ACF returns false when no fields

        WP_Mock::userFunction('get_permalink')
            ->once()
            ->with($postId)
            ->andReturn('https://example.com/group/200');

        $result = $this->factory->createFromSource($postId);

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals($postId, $result->getId());
        $this->assertEquals('', $result->getTitle());
        $this->assertEquals('', $result->getEmail());
        $this->assertEquals([], $result->getMeetingIds());
    }

    /**
     * @test
     */
    public function it_handles_single_meeting_id_as_non_array(): void
    {
        $postId = 300;
        $post = $this->createMockPost([
            'ID' => $postId,
            'post_type' => GroupFields::GROUP_POST_TYPE,
        ]);

        $acfData = [
            GroupFields::TITLE => 'Single Meeting Group',
            GroupFields::GENERIC_EMAIL => '',
            GroupFields::MEETING => 42, // Single ID, not array
        ];

        WP_Mock::userFunction('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        WP_Mock::userFunction('get_fields')
            ->once()
            ->with($postId)
            ->andReturn($acfData);

        WP_Mock::userFunction('get_permalink')
            ->once()
            ->with($postId)
            ->andReturn('');

        $result = $this->factory->createFromSource($postId);

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals([42], $result->getMeetingIds());
    }

    /**
     * @test
     */
    public function it_handles_empty_meeting_field(): void
    {
        $postId = 400;
        $post = $this->createMockPost([
            'ID' => $postId,
            'post_type' => GroupFields::GROUP_POST_TYPE,
        ]);

        $acfData = [
            GroupFields::TITLE => 'No Meeting Group',
            GroupFields::GENERIC_EMAIL => '',
            GroupFields::MEETING => '', // Empty string
        ];

        WP_Mock::userFunction('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        WP_Mock::userFunction('get_fields')
            ->once()
            ->with($postId)
            ->andReturn($acfData);

        WP_Mock::userFunction('get_permalink')
            ->once()
            ->with($postId)
            ->andReturn('');

        $result = $this->factory->createFromSource($postId);

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals([], $result->getMeetingIds());
    }

    /**
     * @test
     */
    public function it_handles_false_permalink(): void
    {
        $postId = 500;
        $post = $this->createMockPost([
            'ID' => $postId,
            'post_type' => GroupFields::GROUP_POST_TYPE,
        ]);

        WP_Mock::userFunction('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        WP_Mock::userFunction('get_fields')
            ->once()
            ->with($postId)
            ->andReturn([
                GroupFields::TITLE => 'Test',
                GroupFields::GENERIC_EMAIL => '',
                GroupFields::MEETING => [],
            ]);

        WP_Mock::userFunction('get_permalink')
            ->once()
            ->with($postId)
            ->andReturn(false); // WordPress returns false on failure

        $result = $this->factory->createFromSource($postId);

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals('', $result->getLink());
    }
}
