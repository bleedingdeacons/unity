<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface Cache
 *
 * Defines the contract for cache implementations
 */
interface Cache
{
    /**
     * Get a cached value
     *
     * @param string $key Cache key
     * @param string $group Optional cache group
     * @return mixed Cached value or false if not found
     */
    public function get(string $key, string $group = '');

    /**
     * Get several cached values in one call
     *
     * Mirrors wp_cache_get_multiple(): every key asked for appears in the
     * result, with false for the ones the cache does not hold. One round trip
     * to a remote cache rather than one per key, which is the whole point —
     * over a few hundred members the difference is a few hundred round trips.
     *
     * @param array<int, string> $keys Cache keys
     * @param string $group Optional cache group
     * @return array<string, mixed> Keyed by cache key; false where not found
     */
    public function getMultiple(array $keys, string $group = ''): array;

    /**
     * Set a cached value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string $group Optional cache group
     * @param int $expire Optional expiration in seconds
     * @return bool Success status
     */
    public function set(string $key, mixed $value, string $group = '', int $expire = 0): bool;

    /**
     * Delete a cached value
     *
     * @param string $key Cache key
     * @param string $group Optional cache group
     * @return bool Success status
     */
    public function delete(string $key, string $group = ''): bool;

    /**
     * Flush all cached values
     *
     * @return void
     */
    public function flush(): void;
}
