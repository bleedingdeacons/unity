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
