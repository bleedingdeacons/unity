<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Unity\Core\Interfaces\Configuration;
use Unity\Core\UnityConfiguration;

/*
 * Tests for {@see UnityConfiguration}, the in-memory key → array config store
 * behind the Configuration binding.
 */

it('is a configuration', function () {
    expect(new UnityConfiguration())->toBeInstanceOf(Configuration::class);
});

it('stores and returns a config section', function () {
    $config = new UnityConfiguration();
    $config->setConfig('members', ['post_type' => 'member']);

    expect($config->getConfig('members'))->toBe(['post_type' => 'member']);
});

it('returns null for an unknown key', function () {
    expect((new UnityConfiguration())->getConfig('missing'))->toBeNull();
});

it('overwrites a section on a second set', function () {
    $config = new UnityConfiguration();
    $config->setConfig('k', ['a' => 1]);
    $config->setConfig('k', ['b' => 2]);

    expect($config->getConfig('k'))->toBe(['b' => 2]);
});
