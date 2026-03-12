<?php

declare(strict_types=1);

namespace Unity\Core\Interfaces;

/**
 * Interface Configuration
 *
 * Allows cross-implementation configuration storage.
 * Used to share field mappings (e.g. ACF field names) between
 * the adapter layer (TSML-for-Unity) and consuming plugins (Amber).
 */
interface Configuration
{
    /**
     * Store a configuration array under the given key.
     *
     * @param string $key    Identifier for the configuration (typically an interface FQCN).
     * @param array  $source The configuration data (e.g. field-name constants).
     * @return void
     */
    public function setConfig(string $key, array $source): void;

    /**
     * Retrieve a configuration array by key.
     *
     * @param string $key Identifier for the configuration.
     * @return array|null The stored configuration, or null if not set.
     */
    public function getConfig(string $key): ?array;
}