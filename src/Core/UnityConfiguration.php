<?php

declare(strict_types=1);

namespace Unity\Core;

use Unity\Core\Interfaces\Configuration;

/**
 * UnityConfiguration class
 */
final class UnityConfiguration implements Configuration
{
    protected array $storage = [];

    public function setConfig(string $key, array $source): void
    {
        $this->storage[$key] = $source;
    }

    public function getConfig(string $key): ?array
    {
        return $this->storage[$key] ?? null;
    }
}