<?php

declare(strict_types=1);

namespace Unity\Groups;

use Unity\Groups\Interfaces\GroupFactoryInterface;
use Unity\Groups\Interfaces\GroupInterface;
use function get_fields;
use function get_permalink;
use function get_post;

/**
 * Concrete Group Factory class
 */
class GroupFactory implements GroupFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFromSource(int $sourceId): ?GroupInterface
    {
        $post = get_post($sourceId);
        
        if (!$post || $post->post_type !== GroupFields::GROUP_POST_TYPE) {
            return null;
        }
        
        $acfData = [];

        if (function_exists('get_fields')) {
            $acfData = get_fields($sourceId) ?: [];
        }
    
        $acfData = array_merge([
            GroupFields::TITLE => '',
            GroupFields::GENERIC_EMAIL => '',
            GroupFields::MEETING => [],
        ], $acfData);

        $link = get_permalink($sourceId) ?: '';

        $meetingIds = $acfData[GroupFields::MEETING];
        if (!is_array($meetingIds)) {
            $meetingIds = empty($meetingIds) ? [] : [$meetingIds];
        }
        
        return new Group(
            $sourceId,
            (string) $acfData[GroupFields::TITLE],
            (string) $acfData[GroupFields::GENERIC_EMAIL],
            $meetingIds,
            $link
        );
    }
}
