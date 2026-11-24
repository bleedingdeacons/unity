<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

/**
 * A small set of named string values kept against one model.
 *
 * <p><b>Generic on purpose.</b> This sits at the top of the model hierarchy
 * so any model can extend it: Member, Group, Meeting, Position. Today only
 * {@see \Unity\Members\Interfaces\Member} does. Values are for things a
 * plugin needs to remember about a record that Unity's own fields don't
 * cover, such as Reach's `AppleUserId`. Each implementation decides where
 * they are stored. The TSML member keeps them in post meta.</p>
 *
 * <p><b>Values are live, not part of the snapshot.</b> Models are immutable
 * value objects. Their fields are fixed when built, and changed by building a
 * copy and saving it. Stored values are not fields. Every call reads from or
 * writes to storage right away, so `setUnityExtendedValue()` takes effect without a
 * repository save, and two copies of the same model always agree. That is
 * why a model can carry the store and still be immutable.</p>
 *
 * <p><b>Keys.</b> Letters, digits and underscores, 1–64 characters, matching
 * {@see self::KEY_PATTERN}. Case is kept, so `AppleUserId` reads back as
 * `AppleUserId` and is a different key from `appleuserid`. Every method
 * refuses any other key with {@see InvalidArgumentException}. Silently
 * normalising a key would let two callers write the same value under keys
 * they believe are different. {@see \Unity\Core\StoredValueKey::assertValid()}
 * is the one place that check is made.</p>
 *
 * <p><b>Not for secrets.</b> Values are stored in plain text and shown to
 * anyone who can edit the record. Amber's member edit page lists them all.</p>
 *
 * @see ValueLookup for finding a model by one of its stored values.
 */
interface KeyValueStoreInterface
{
    /** Keys this store accepts. */
    public const KEY_PATTERN = '/^[A-Za-z0-9_]{1,64}$/';

    /**
     * The value stored under a key, or null when there is none.
     *
     * @throws InvalidArgumentException When the key does not match {@see self::KEY_PATTERN}.
     */
    public function getUnityExtendedValue(string $key): ?string;

    /**
     * Store a value under a key, replacing any value already there.
     *
     * Written to storage immediately; no repository save is needed or implied.
     *
     * @throws InvalidArgumentException When the key does not match {@see self::KEY_PATTERN}.
     */
    public function setUnityExtendedValue(string $key, string $value): void;

    /**
     * Remove a key and its value. Removing a key that is not set is not an error.
     *
     * @throws InvalidArgumentException When the key does not match {@see self::KEY_PATTERN}.
     */
    public function deleteUnityExtendedValue(string $key): void;

    /**
     * Every stored value, keyed by its key, sorted by key.
     *
     * An empty array when nothing is stored.
     *
     * @return array<string, string>
     */
    public function allUnityExtendedValues(): array;
}
