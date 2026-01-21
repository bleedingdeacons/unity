<?php

declare(strict_types=1);

namespace Unity\Groups;

use Exception;
use TsmlForUnity\TsmlGroupFields;
use Unity\Groups\Interfaces\GroupInterface;
use Unity\Groups\Interfaces\GroupRepositoryInterface;
use function add_action;
use function do_action;
use function get_post;
use function get_post_type;
use function wp_update_post;
use const WP_DEBUG;

/**
 * Class GroupChangeTracker
 * 
 * Tracks changes to groups via ACF and fires the group_changed hook
 * when actual changes are detected.
 */
class GroupChangeTracker
{
    private static ?GroupInterface $originalGroup = null;
    private GroupRepositoryInterface $repository;

    /**
     * Constructor
     * 
     * @param GroupRepositoryInterface $repository Repository for accessing groups
     */
    public function __construct(GroupRepositoryInterface $repository)
    {
        $this->repository = $repository;

        add_action('acf/save_post', [$this, 'captureOriginalGroup'], 1);
        add_action('acf/save_post', [$this, 'checkForChanges'], 20);
    }

    /**
     * Capture the original group before ACF makes changes
     * 
     * @param int $postId The post ID being saved
     * @return void
     */
    public function captureOriginalGroup(int $postId): void
    {
        if (get_post_type($postId) !== TsmlGroupFields::GROUP_POST_TYPE) {
            return;
        }

        try {
            self::$originalGroup = $this->repository->findById($postId);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Original group captured for post ID: ' . $postId);
            }
        } catch (Exception $e) {
            error_log('Error capturing original group: ' . $e->getMessage());
        }
    }

    /**
     * Check for changes after ACF has saved all fields
     * 
     * @param int $postId The post ID being saved
     * @return void
     */
    public function checkForChanges(int $postId): void
    {
        if (get_post_type($postId) !== TsmlGroupFields::GROUP_POST_TYPE) {
            return;
        }

        if (!self::$originalGroup) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('No original group captured for comparison, post ID: ' . $postId);
            }
            return;
        }

        try {
            $updatedGroup = $this->repository->findById($postId);

            if (!$updatedGroup) {
                error_log('Could not fetch updated group for post ID: ' . $postId);
                return;
            }

            if ($this->hasGroupChanged(self::$originalGroup, $updatedGroup)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Changes detected in group ID: ' . $postId . ', firing group_changed hook');
                }

                $post = get_post($postId);
                if ($post && $post->post_title !== $updatedGroup->getTitle()) {
                    wp_update_post([
                        'ID' => $postId,
                        'post_title' => $updatedGroup->getTitle()
                    ]);
                }

                do_action('group_changed', $updatedGroup, self::$originalGroup);
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('No changes detected in group ID: ' . $postId);
                }
            }

            self::$originalGroup = null;
        } catch (Exception $e) {
            error_log('Error checking for group changes: ' . $e->getMessage());
        }
    }

    /**
     * Check if a group has changed by comparing its properties
     * 
     * @param GroupInterface $originalGroup The original group before changes
     * @param GroupInterface $updatedGroup The updated group after changes
     * @return bool True if the group has changed, false otherwise
     */
    private function hasGroupChanged(GroupInterface $originalGroup, GroupInterface $updatedGroup): bool
    {
        if ($originalGroup->getTitle() !== $updatedGroup->getTitle()) {
            return true;
        }

        if ($originalGroup->getEmail() !== $updatedGroup->getEmail()) {
            return true;
        }

        $originalMeetingIds = $originalGroup->getMeetingIds();
        $updatedMeetingIds = $updatedGroup->getMeetingIds();

        sort($originalMeetingIds);
        sort($updatedMeetingIds);

        if (count($originalMeetingIds) !== count($updatedMeetingIds)) {
            return true;
        }

        for ($i = 0; $i < count($originalMeetingIds); $i++) {
            if ($originalMeetingIds[$i] !== $updatedMeetingIds[$i]) {
                return true;
            }
        }

        return false;
    }
}
