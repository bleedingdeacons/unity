<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Core\Interfaces\Cache;
use function wp_cache_delete;
use function wp_cache_flush;
use function wp_cache_get;
use function wp_cache_set;

/**
 * WordPress cache adapter
 */
class WordPressCache implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        wp_cache_flush();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, string $group = ''): mixed
    {
        return wp_cache_get($key, $group);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, string $group = '', int $expire = 0): bool
    {
        return wp_cache_set($key, $value, $group, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key, string $group = ''): bool
    {
        return wp_cache_delete($key, $group);
    }
}
