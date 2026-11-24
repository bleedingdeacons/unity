<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

/**
 * Find a model by one of its stored values.
 *
 * The repository half of {@see KeyValueStoreInterface}, and generic for the
 * same reason: it sits at the top of the repository hierarchy so any
 * repository whose models carry a store can extend it. Today only
 * {@see \Unity\Members\Interfaces\MemberRepository} does, narrowing the
 * return type to its own model.
 *
 * <p>A stored value used for lookup is expected to identify one record —
 * Reach's `AppleUserId` is the case this was written for. When more than one
 * record carries the same key and value, the answer is null rather than an
 * arbitrary pick: a lookup used to decide who somebody is must not guess.</p>
 *
 * @template T of KeyValueStoreInterface
 */
interface ValueLookup
{
    /**
     * The one model whose stored value under `$key` is exactly `$value`.
     *
     * Null when none matches, when more than one does, or when `$value` is
     * empty.
     *
     * @return T|null
     * @throws InvalidArgumentException When the key does not match {@see KeyValueStoreInterface::KEY_PATTERN}.
     */
    public function findByValue(string $key, string $value): ?KeyValueStoreInterface;
}
