<?php

declare(strict_types=1);

namespace Unity\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use Unity\Core\Interfaces\KeyValueStoreInterface;

/**
 * The one check every {@see KeyValueStoreInterface} makes on a key.
 *
 * Kept in Unity, beside the contract, so the implementations in other
 * plugins and the test doubles here cannot drift apart on what a valid key
 * is.
 */
final class StoredValueKey
{
    private function __construct()
    {
    }

    /**
     * Refuse a key that does not match {@see KeyValueStoreInterface::KEY_PATTERN}.
     *
     * @throws InvalidArgumentException
     */
    public static function assertValid(string $key): void
    {
        if (preg_match(KeyValueStoreInterface::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Stored value keys are 1-64 letters, digits or underscores; "%s" is not.',
                $key,
            ));
        }
    }
}
