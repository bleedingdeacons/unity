<?php

declare(strict_types=1);

namespace Unity\Tests;

use BleedingDeacons\WpMocks\TestCase as WpMocksTestCase;
use Unity\Plugin;

/**
 * Base TestCase for Unity plugin tests.
 *
 * The Brain Monkey and Mockery lifecycle, the WpState reset and the hook
 * assertions all come from the shared wp-mocks base class. assertActionAdded()
 * and assertFilterAdded() used to be defined here — and in four other plugins —
 * over WP_Mock::onActionAdded(); they now come from the shared HookAssertions
 * trait. What remains below is only what is specific to Unity.
 */
abstract class TestCase extends WpMocksTestCase
{
    /**
     * Reset the global Plugin singleton so no static state leaks between
     * tests. Ordering matters: this runs before the parent closes Mockery, so
     * a mock container held by the instance is still valid while it is
     * released.
     */
    protected function tearDown(): void
    {
        Plugin::setInstance(null);

        parent::tearDown();
    }

    /**
     * Create an isolated Plugin instance with a fresh container.
     *
     * Convenience helper for tests that need a real (non-mocked) container.
     * The instance is NOT registered as the global default; call
     * Plugin::setInstance() yourself if your test requires that.
     */
    protected function createPluginInstance(): Plugin
    {
        return Plugin::create();
    }

    /**
     * Create a mock WordPress post object.
     *
     * @param array<string, mixed> $properties Properties to set on the post
     */
    protected function createMockPost(array $properties = []): object
    {
        $defaults = [
            'ID' => 1,
            'post_author' => '1',
            'post_date' => '2024-01-01 00:00:00',
            'post_date_gmt' => '2024-01-01 00:00:00',
            'post_content' => 'Test content',
            'post_title' => 'Test Post',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_password' => '',
            'post_name' => 'test-post',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => '2024-01-01 00:00:00',
            'post_modified_gmt' => '2024-01-01 00:00:00',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => 'http://example.com/?p=1',
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' => '0',
        ];

        return (object) array_merge($defaults, $properties);
    }
}
