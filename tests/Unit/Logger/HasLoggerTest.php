<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Logger;

use Brain\Monkey\Functions;
use ReflectionClass;
use Unity\Logger\HasLogger;

/** A class that uses the trait without overriding logChannel(). */
class TraitLoggerHost
{
    use HasLogger;
}

/*
 * Tests for the {@see HasLogger} trait — the safe logging façade that resolves
 * a Sentinel log channel via wp_log() and no-ops when it is unavailable. The
 * channel is memoised per using-class, so the static cache is reset before
 * each test.
 */

/**
 * Reset the trait's per-class static channel cache so each test starts
 * from a clean slate (the property is private static on the trait).
 */
function resetLoggerChannel(): void
{
    $ref = new ReflectionClass(TraitLoggerHost::class);
    if ($ref->hasProperty('loggerChannel')) {
        // No setAccessible() call: it has been a no-op since PHP 8.1 —
        // which this plugin requires — and is deprecated from 8.5.
        $ref->getProperty('loggerChannel')->setValue(null, null);
    }
}

beforeEach(function () {
    resetLoggerChannel();
});

afterEach(function () {
    resetLoggerChannel();
});

it('resolves the channel once and memoises it', function () {
    $channel = new \Sentinel_Log_Channel();

    // logChannel() derives the name from the class basename via
    // sanitize_key(); wp_log() is called exactly once and the result cached.
    Functions\expect('wp_log')->once()->with('traitloggerhost')->andReturn($channel);

    $first  = TraitLoggerHost::log();
    $second = TraitLoggerHost::log();

    expect($first)->toBe($channel)
        ->and($second)->toBe($channel, 'channel must be memoised, not re-resolved');
});

it('forwards every level to the channel', function () {
    $channel = new \Sentinel_Log_Channel();
    Functions\expect('wp_log')->andReturn($channel);

    TraitLoggerHost::logEmergency('m', ['k' => 'v']);
    TraitLoggerHost::logAlert('m');
    TraitLoggerHost::logCritical('m');
    TraitLoggerHost::logError('m');
    TraitLoggerHost::logWarning('m');
    TraitLoggerHost::logNotice('m');
    TraitLoggerHost::logInfo('m');
    TraitLoggerHost::logDebug('m');

    expect($channel->levels())
        ->toBe(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);
});
