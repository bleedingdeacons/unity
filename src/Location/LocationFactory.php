<?php

declare(strict_types=1);

namespace Unity\Locations;

use Unity\Locations\Interfaces\LocationFactoryInterface;
use Unity\Locations\Interfaces\LocationInterface;
use function get_permalink;
use function get_post;
use function get_post_meta;
use function wp_get_post_terms;

/**
 * Concrete Location Factory class
 *
 * Creates Location objects from WordPress post IDs, typically
 * from the TSML plugin's tsml_location post type.
 */
class LocationFactory implements LocationFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createFromSource(int $sourceId): ?LocationInterface
    {
        $post = get_post($sourceId);

        if (!$post || $post->post_type !== LocationFields::LOCATION_POST_TYPE) {
            return null;
        }

        $meta = $this->getLocationMeta($sourceId);
        $link = get_permalink($sourceId) ?: '';
        $region = $this->getRegion($sourceId);
        $meetingIds = $this->getMeetingIds($sourceId);

        return new Location(
            $sourceId,
            $post->post_title ?: '',
            (string) ($meta[LocationFields::ADDRESS] ?? ''),
            (string) ($meta[LocationFields::CITY] ?? ''),
            (string) ($meta[LocationFields::STATE] ?? ''),
            (string) ($meta[LocationFields::POSTAL_CODE] ?? ''),
            (string) ($meta[LocationFields::COUNTRY] ?? ''),
            $region,
            (string) ($meta[LocationFields::NOTES] ?? ''),
            $link,
            $this->parseFloat($meta[LocationFields::LATITUDE] ?? null),
            $this->parseFloat($meta[LocationFields::LONGITUDE] ?? null),
            (string) ($meta[LocationFields::TIMEZONE] ?? ''),
            $meetingIds
        );
    }

    /**
     * Get all location meta data
     *
     * @param int $postId The post ID
     * @return array Associative array of meta values
     */
    private function getLocationMeta(int $postId): array
    {
        $metaKeys = [
            LocationFields::ADDRESS,
            LocationFields::CITY,
            LocationFields::STATE,
            LocationFields::POSTAL_CODE,
            LocationFields::COUNTRY,
            LocationFields::NOTES,
            LocationFields::LATITUDE,
            LocationFields::LONGITUDE,
            LocationFields::TIMEZONE,
        ];

        $meta = [];
        foreach ($metaKeys as $key) {
            $meta[$key] = get_post_meta($postId, $key, true);
        }

        return $meta;
    }

    /**
     * Get the region name from taxonomy
     *
     * @param int $postId The post ID
     * @return string Region name or empty string
     */
    private function getRegion(int $postId): string
    {
        $terms = wp_get_post_terms($postId, 'tsml_region', ['fields' => 'names']);

        if (is_array($terms) && !empty($terms)) {
            return (string) $terms[0];
        }

        return '';
    }

    /**
     * Get meeting IDs associated with this location
     *
     * @param int $postId The location post ID
     * @return array Array of meeting post IDs
     */
    private function getMeetingIds(int $postId): array
    {
        global $wpdb;

        if (!$wpdb) {
            return [];
        }

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = 'location_id' AND meta_value = %d",
            $postId
        ));

        return array_map('intval', $results ?: []);
    }

    /**
     * Parse a value as float, returning null if invalid
     *
     * @param mixed $value The value to parse
     * @return float|null The parsed float or null
     */
    private function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
