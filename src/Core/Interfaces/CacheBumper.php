<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Something holding cached state that can be invalidated wholesale.
 *
 * Deliberately one method. It exists so that
 * {@see \Unity\Core\PostTypeCacheInvalidator} can clear a cache without
 * knowing what is in it or how the keys are shaped — the caching repositories
 * move a version prefix, but an implementation that deleted keys one by one
 * would satisfy this just as well.
 */
interface CacheBumper
{
    /**
     * Invalidate everything held, so the next read rebuilds it.
     *
     * @return void
     */
    public function bump(): void;
}
