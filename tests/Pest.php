<?php

declare(strict_types=1);

// Pest configuration.
//
// The split tests/TestCase.php describes still holds. A test that reaches
// WordPress — the wp_cache_* adapter, wp_log(), the post and meta hooks, the
// Plugin façade's register_deactivation_hook() — has to run on
// Unity\Tests\TestCase, which wraps wp-mocks' and resets the global Plugin
// instance after each test. Brain Monkey only defines its functions inside
// that TestCase's setUp(), and Mockery expectations are only verified there.
//
// The pure-PHP tests — the container, configuration, the credential row, the
// two ACF-backed enums and validateRegistrations() — need none of it and stay
// on Pest's default, plain PHPUnit.
//
// So this list is load-bearing. A new WordPress-coupled test file belongs in
// one of these directories, or has to be named here.

use Unity\Tests\TestCase;

pest()->extend(TestCase::class)->in(
    'Unit/Logger',
    'Unit/Meetings',
    'Unit/Testing',
    'Unit/PluginTest.php',
    'Unit/Core/WordPressCacheTest.php',
    'Unit/Members/CachingMemberRepositoryTest.php',
    'Unit/Members/MemberCacheInvalidatorTest.php',
);
