<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Brain\Monkey\Functions;
use Unity\Core\Interfaces\Cache;
use Unity\Core\WordPressCache;

/*
 * Tests for {@see WordPressCache} — the thin adapter mapping the Cache
 * interface onto WordPress's wp_cache_* object-cache functions. Each method
 * must forward its arguments verbatim and return what WordPress returns.
 */

beforeEach(function () {
    $this->cache = new WordPressCache();
});

it('is a cache', function () {
    expect($this->cache)->toBeInstanceOf(Cache::class);
});

it('delegates flush to wp_cache_flush', function () {
    Functions\expect('wp_cache_flush')->once()->andReturn(true);

    // flush() returns void; the ->once() expectation is verified on
    // tearDown. Assert on the void return so the test is not risky.
    expect($this->cache->flush())->toBeNull();
});

it('forwards the key and group on get and returns the value', function () {
    Functions\expect('wp_cache_get')
        ->once()
        ->with('member:1', 'unity')
        ->andReturn(['id' => 1]);

    expect($this->cache->get('member:1', 'unity'))->toBe(['id' => 1]);
});

it('forwards the keys and group on getMultiple and returns what WordPress answers', function () {
    Functions\expect('wp_cache_get_multiple')
        ->once()
        ->with(['member:1', 'member:2'], 'unity')
        ->andReturn(['member:1' => ['id' => 1], 'member:2' => false]);

    // The absent key still appears, carrying false — the contract callers
    // rely on to tell a miss from a value.
    expect($this->cache->getMultiple(['member:1', 'member:2'], 'unity'))
        ->toBe(['member:1' => ['id' => 1], 'member:2' => false]);
});

it('forwards all arguments on set and returns the result', function () {
    Functions\expect('wp_cache_set')
        ->once()
        ->with('member:1', ['id' => 1], 'unity', 300)
        ->andReturn(true);

    expect($this->cache->set('member:1', ['id' => 1], 'unity', 300))->toBeTrue();
});

it('forwards the key and group on delete and returns the result', function () {
    Functions\expect('wp_cache_delete')
        ->once()
        ->with('member:1', 'unity')
        ->andReturn(true);

    expect($this->cache->delete('member:1', 'unity'))->toBeTrue();
});
