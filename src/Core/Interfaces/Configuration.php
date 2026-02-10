<?php
declare(strict_types=1);

namespace Unity\Core\Interfaces;

/**
* Interface Configuration
*
* Allow's for cross-implementation configuration
*/
interface Configuration
{
    /**
     * Store configuration field.
     *
     * @param array $source The contact source data.
     */
    public function setConfig($key, array $source);

    /**
     * Retrieve configuration field.
     *
     * @param string $key The contact source data.
     * @return array|null $fields TsmlContact object.
     */
    public function getConfig(string $key): ?array;

}