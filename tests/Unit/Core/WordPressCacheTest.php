<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use function Brain\Monkey\Functions\expect;
use Unity\Core\Interfaces\Cache;
use Unity\Core\WordPressCache;
use Unity\Tests\TestCase;

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

    #[Test]
    public function it_is_a_cache(): void
    {
        $this->assertInstanceOf(Cache::class, $this->cache);
    }

    #[Test]
    public function flush_delegates_to_wp_cache_flush(): void
    {
        expect('wp_cache_flush')->once()->andReturn(true);

        // flush() returns void; the ->once() expectation is verified on
        // tearDown. Assert on the void return so the test is not risky.
        $this->assertNull($this->cache->flush());
    }

    #[Test]
    public function get_forwards_key_and_group_and_returns_the_value(): void
    {
        expect('wp_cache_get')
            ->once()
            ->with('member:1', 'unity')
            ->andReturn(['id' => 1]);

        $this->assertSame(['id' => 1], $this->cache->get('member:1', 'unity'));
    }

    #[Test]
    public function get_multiple_forwards_the_keys_and_group_and_returns_what_wordpress_answers(): void
    {
        expect('wp_cache_get_multiple')
            ->once()
            ->with(['member:1', 'member:2'], 'unity')
            ->andReturn(['member:1' => ['id' => 1], 'member:2' => false]);

        // The absent key still appears, carrying false — the contract callers
        // rely on to tell a miss from a value.
        $this->assertSame(
            ['member:1' => ['id' => 1], 'member:2' => false],
            $this->cache->getMultiple(['member:1', 'member:2'], 'unity')
        );
    }

    #[Test]
    public function set_forwards_all_arguments_and_returns_the_result(): void
    {
        expect('wp_cache_set')
            ->once()
            ->with('member:1', ['id' => 1], 'unity', 300)
            ->andReturn(true);

        $this->assertTrue($this->cache->set('member:1', ['id' => 1], 'unity', 300));
    }

    #[Test]
    public function delete_forwards_key_and_group_and_returns_the_result(): void
    {
        expect('wp_cache_delete')
            ->once()
            ->with('member:1', 'unity')
            ->andReturn(true);

        $this->assertTrue($this->cache->delete('member:1', 'unity'));
    }
}
