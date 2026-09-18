<?php

declare(strict_types=1);

namespace Unity\Members;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Core\PostTypeCacheInvalidator;

/**
 * Clears {@see CachingMemberRepository} when a member changes anywhere.
 *
 * All of the behaviour is {@see PostTypeCacheInvalidator}'s; this subclass
 * exists because tsml-for-unity constructs it by name and a site can run a
 * newer Unity than its companion plugins. Dropping the class would fatal such
 * a site on unity/loaded rather than degrade, which is the trade Unity makes
 * everywhere else. New callers should use the parent directly.
 */
final class MemberCacheInvalidator extends PostTypeCacheInvalidator
{
}
