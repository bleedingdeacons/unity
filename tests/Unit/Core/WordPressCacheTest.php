<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Unity\Core\Interfaces\Cache;
use Unity\Core\WordPressCache;
use Unity\Tests\TestCase;
use WP_Mock;

/**
 * Tests for {@see WordPressCache} — the thin adapter mapping the Cache
 * interface onto WordPress's wp_cache_* object-cache functions. Each method
 * must forward its arguments verbatim and return what WordPress returns.
 */
class WordPressCacheTest extends TestCase
{
    private WordPressCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new WordPressCache();
    }

    /**
     * @test
     */
    public function it_is_a_cache(): void
    {
        $this->assertInstanceOf(Cache::class, $this->cache);
    }

    /**
     * @test
     */
    public function flush_delegates_to_wp_cache_flush(): void
    {
        WP_Mock::userFunction('wp_cache_flush')->once()->andReturn(true);

        // flush() returns void; the ->once() expectation is verified on
        // tearDown. Assert on the void return so the test is not risky.
        $this->assertNull($this->cache->flush());
    }

    /**
     * @test
     */
    public function get_forwards_key_and_group_and_returns_the_value(): void
    {
        WP_Mock::userFunction('wp_cache_get')
            ->once()
            ->with('member:1', 'unity')
            ->andReturn(['id' => 1]);

        $this->assertSame(['id' => 1], $this->cache->get('member:1', 'unity'));
    }

    /**
     * @test
     */
    public function set_forwards_all_arguments_and_returns_the_result(): void
    {
        WP_Mock::userFunction('wp_cache_set')
            ->once()
            ->with('member:1', ['id' => 1], 'unity', 300)
            ->andReturn(true);

        $this->assertTrue($this->cache->set('member:1', ['id' => 1], 'unity', 300));
    }

    /**
     * @test
     */
    public function delete_forwards_key_and_group_and_returns_the_result(): void
    {
        WP_Mock::userFunction('wp_cache_delete')
            ->once()
            ->with('member:1', 'unity')
            ->andReturn(true);

        $this->assertTrue($this->cache->delete('member:1', 'unity'));
    }
}
