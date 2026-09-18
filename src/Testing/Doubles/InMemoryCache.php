<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\Interfaces\Cache;

/**
 * An array-backed Cache for tests.
 *
 * Models the parts of a persistent object cache that callers can actually
 * observe: entries survive until deleted, a miss answers false rather than
 * null, and groups are separate namespaces.
 *
 * {@see evict()} is the one thing a real cache does that a plain array will
 * not — Memcached discards least-recently-used entries when it runs short of
 * memory, whatever their expiry, and code that assumes an entry it wrote is
 * still there is wrong in a way no ordinary test would catch.
 */
final class InMemoryCache implements Cache
{
    /** @var array<string, array<string, mixed>> Group name to key to value. */
    private array $entries = [];

    /** @var array<int, string> Every key read, in order, as "group/key". */
    public array $reads = [];

    /** @var array<int, string> Every key written, in order, as "group/key". */
    public array $writes = [];

    /**
     * Expiry passed to the last set() for a key, as "group/key" to seconds.
     *
     * @var array<string, int>
     */
    public array $expiries = [];

    public function get(string $key, string $group = '')
    {
        $this->reads[] = $group . '/' . $key;

        return $this->entries[$group][$key] ?? false;
    }

    public function set(string $key, mixed $value, string $group = '', int $expire = 0): bool
    {
        $this->writes[] = $group . '/' . $key;
        $this->expiries[$group . '/' . $key] = $expire;
        $this->entries[$group][$key] = $value;

        return true;
    }

    public function delete(string $key, string $group = ''): bool
    {
        if (!isset($this->entries[$group][$key])) {
            return false;
        }

        unset($this->entries[$group][$key]);

        return true;
    }

    public function flush(): void
    {
        $this->entries = [];
    }

    /**
     * Discard one entry the way a full cache would, without recording a
     * delete: nothing asked for this and no caller knows it happened.
     */
    public function evict(string $key, string $group = ''): void
    {
        unset($this->entries[$group][$key]);
    }
}
