<?php

declare(strict_types=1);

namespace Unity\Positions;

use Unity\Positions\Interfaces\PositionFactoryInterface;
use Unity\Positions\Interfaces\PositionInterface;
use Unity\Positions\Interfaces\PositionRepositoryInterface;
use Exception;
use function get_posts;
use function is_wp_error;
use function update_field;
use function wp_insert_post;
use function wp_parse_args;
use function wp_update_post;

/**
 * Concrete Position Repository class
 */
class PositionRepository implements PositionRepositoryInterface
{
    private PositionFactoryInterface $factory;
    
    /**
     * PositionRepository constructor
     * 
     * @param PositionFactoryInterface $factory The position factory
     */
    public function __construct(PositionFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?PositionInterface
    {
        return $this->factory->createFromSource($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $args = []): array
    {
        $defaultArgs = [
            'post_type' => PositionFields::POSITION_POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $queryArgs = wp_parse_args($args, $defaultArgs);
        $posts = get_posts($queryArgs);
        $positions = [];

        foreach ($posts as $post) {
            $position = $this->factory->createFromSource($post->ID);
            if ($position !== null) {
                $positions[] = $position;
            }
        }

        return $positions;
    }

    /**
     * {@inheritdoc}
     */
    public function save(PositionInterface $position): bool
    {
        $postId = $position->getId();
        
        if ($postId > 0) {
            return $this->update($position);
        }

        if (!$position->isValid()) {
            return false;
        }

        $postData = [
            'post_type' => PositionFields::POSITION_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $position->getLongName(),
            'post_content' => '',
        ];

        $result = wp_insert_post($postData, true);

        if (is_wp_error($result)) {
            return false;
        }

        $postId = $result;

        if (function_exists('update_field')) {
            update_field(PositionFields::MINIMUM_SOBRIETY, $position->getMinimumSobriety(), $postId);
            update_field(PositionFields::TERM_YEARS, $position->getTermYears(), $postId);
            update_field(PositionFields::EMAIL_ADDRESS, $position->getEmail(), $postId);
            update_field(PositionFields::LONG_NAME, $position->getLongName(), $postId);
            update_field(PositionFields::SHORT_DESCRIPTION, $position->getShortDescription(), $postId);
            update_field(PositionFields::SUMMARY, $position->getSummary(), $postId);
        }

        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(PositionInterface $position): bool
    {
        $postId = $position->getId();
        
        if ($postId <= 0) {
            return false;
        }

        if (!$position->isValid()) {
            return false;
        }

        $postData = [
            'ID' => $postId,
            'post_title' => $position->getLongName(),
            'post_type' => PositionFields::POSITION_POST_TYPE,
            'post_status' => 'publish',
        ];

        $result = wp_update_post($postData, true);

        if (is_wp_error($result)) {
            return false;
        }

        if (function_exists('update_field')) {
            update_field(PositionFields::MINIMUM_SOBRIETY, $position->getMinimumSobriety(), $postId);
            update_field(PositionFields::TERM_YEARS, $position->getTermYears(), $postId);
            update_field(PositionFields::EMAIL_ADDRESS, $position->getEmail(), $postId);
            update_field(PositionFields::LONG_NAME, $position->getLongName(), $postId);
            update_field(PositionFields::SHORT_DESCRIPTION, $position->getShortDescription(), $postId);
            update_field(PositionFields::SUMMARY, $position->getSummary(), $postId);
        }

        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        throw new Exception('Delete is not implemented');
    }
}
