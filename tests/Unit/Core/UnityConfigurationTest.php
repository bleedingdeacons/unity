<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Unity\Core\Interfaces\Configuration;
use Unity\Core\UnityConfiguration;

/**
 * Tests for {@see UnityConfiguration}, the in-memory key → array config store
 * behind the Configuration binding.
 */
class UnityConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_a_configuration(): void
    {
        $this->assertInstanceOf(Configuration::class, new UnityConfiguration());
    }

    /**
     * @test
     */
    public function it_stores_and_returns_a_config_section(): void
    {
        $config = new UnityConfiguration();
        $config->setConfig('members', ['post_type' => 'member']);

        $this->assertSame(['post_type' => 'member'], $config->getConfig('members'));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unknown_key(): void
    {
        $this->assertNull((new UnityConfiguration())->getConfig('missing'));
    }

    /**
     * @test
     */
    public function it_overwrites_a_section_on_a_second_set(): void
    {
        $config = new UnityConfiguration();
        $config->setConfig('k', ['a' => 1]);
        $config->setConfig('k', ['b' => 2]);

        $this->assertSame(['b' => 2], $config->getConfig('k'));
    }
}
