<?php

declare(strict_types=1);

namespace Unity\Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Unity\Plugin;
use WP_Mock;

/**
 * Base TestCase for Unity plugin tests
 *
 * Provides setup and teardown for WP_Mock and Mockery integration.
 * Automatically resets the Plugin global instance between tests to
 * prevent static state leaking across test cases.
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void
    {
        // Reset global Plugin singleton so no state leaks between tests
        Plugin::setInstance(null);

        WP_Mock::tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create an isolated Plugin instance with a fresh container.
     *
     * Convenience helper for tests that need a real (non-mocked) container.
     * The instance is NOT registered as the global default; call
     * Plugin::setInstance() yourself if your test requires that.
     *
     * @return Plugin
     */
    protected function createPluginInstance(): Plugin
    {
        return Plugin::create();
    }

    /**
     * Assert that WordPress hooks were added correctly
     *
     * @param string $action The action hook name
     * @param callable|array $callback The callback
     * @param int $priority The priority
     * @param int $acceptedArgs Number of accepted arguments
     */
    protected function assertActionAdded(string $action, $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->assertTrue(
            WP_Mock::onActionAdded($action)->react($callback, $priority, $acceptedArgs),
            "Failed asserting that action '{$action}' was added."
        );
    }

    /**
     * Assert that WordPress filters were added correctly
     *
     * @param string $filter The filter hook name
     * @param callable|array $callback The callback
     * @param int $priority The priority
     * @param int $acceptedArgs Number of accepted arguments
     */
    protected function assertFilterAdded(string $filter, $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->assertTrue(
            WP_Mock::onFilterAdded($filter)->react($callback, $priority, $acceptedArgs),
            "Failed asserting that filter '{$filter}' was added."
        );
    }

    /**
     * Create a mock WordPress post object
     *
     * @param array $properties Properties to set on the post
     * @return object
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
