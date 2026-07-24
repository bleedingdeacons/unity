<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Logger;

use ReflectionClass;
use Unity\Logger\HasLogger;
use Unity\Tests\TestCase;
use WP_Mock;

/** A class that uses the trait without overriding logChannel(). */
class TraitLoggerHost
{
    use HasLogger;
}

/**
 * Tests for the {@see HasLogger} trait — the safe logging façade that resolves
 * a Sentinel log channel via wp_log() and no-ops when it is unavailable. The
 * channel is memoised per using-class, so the static cache is reset before
 * each test.
 */
class HasLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetChannel();
    }

    protected function tearDown(): void
    {
        $this->resetChannel();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function log_resolves_the_channel_once_and_memoises_it(): void
    {
        $channel = new \Sentinel_Log_Channel();

        // logChannel() derives the name from the class basename via
        // sanitize_key(); wp_log() is called exactly once and the result cached.
        WP_Mock::userFunction('sanitize_key')->andReturnUsing(
            static fn (string $v): string => strtolower($v)
        );
        WP_Mock::userFunction('wp_log')->once()->with('traitloggerhost')->andReturn($channel);

        $first  = TraitLoggerHost::log();
        $second = TraitLoggerHost::log();

        $this->assertSame($channel, $first);
        $this->assertSame($channel, $second, 'channel must be memoised, not re-resolved');
    }

    /**
     * @test
     */
    public function every_level_forwards_to_the_channel(): void
    {
        $channel = new \Sentinel_Log_Channel();
        WP_Mock::userFunction('sanitize_key')->andReturnUsing(
            static fn (string $v): string => strtolower($v)
        );
        WP_Mock::userFunction('wp_log')->andReturn($channel);

        TraitLoggerHost::logEmergency('m', ['k' => 'v']);
        TraitLoggerHost::logAlert('m');
        TraitLoggerHost::logCritical('m');
        TraitLoggerHost::logError('m');
        TraitLoggerHost::logWarning('m');
        TraitLoggerHost::logNotice('m');
        TraitLoggerHost::logInfo('m');
        TraitLoggerHost::logDebug('m');

        $this->assertSame(
            ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
            $channel->calls,
        );
    }

    /**
     * Reset the trait's per-class static channel cache so each test starts
     * from a clean slate (the property is private static on the trait).
     */
    private function resetChannel(): void
    {
        $ref = new ReflectionClass(TraitLoggerHost::class);
        if ($ref->hasProperty('loggerChannel')) {
            $prop = $ref->getProperty('loggerChannel');
            $prop->setAccessible(true);
            $prop->setValue(null, null);
        }
    }
}
