<?php

declare(strict_types=1);

namespace Unity\Locations;

/**
 * ACF Field Constants for Locations
 *
 * These field names correspond to the TSML plugin's location post type fields.
 */
final class LocationFields
{
    /**
     * The WordPress custom post type for locations (from TSML plugin)
     */
    public const LOCATION_POST_TYPE = 'tsml_location';

    /**
     * Locations name/title field
     */
    public const NAME = 'location-name';

    /**
     * Street address field
     */
    public const ADDRESS = 'formatted_address';

    /**
     * City field
     */
    public const CITY = 'city';

    /**
     * State/province field
     */
    public const STATE = 'state';

    /**
     * Postal/zip code field
     */
    public const POSTAL_CODE = 'postal_code';

    /**
     * Country field
     */
    public const COUNTRY = 'country';

    /**
     * Region field (taxonomy term)
     */
    public const REGION = 'region';

    /**
     * Locations notes/description field
     */
    public const NOTES = 'location_notes';

    /**
     * Latitude coordinate field
     */
    public const LATITUDE = 'latitude';

    /**
     * Longitude coordinate field
     */
    public const LONGITUDE = 'longitude';

    /**
     * Timezone field
     */
    public const TIMEZONE = 'timezone';

    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }
}
