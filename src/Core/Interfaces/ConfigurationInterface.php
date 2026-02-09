<?php
declare(strict_types=1);

namespace Unity\Core\Interfaces;

use Unity\Configuration\Interfaces\The;

/**
* Interface ConfigurationInterface
*
* Allow's for cross-implementation configuration
*/
interface ConfigurationInterface
{
    /**
     * Store configuration field.
     *
     * @param array $source The contact source data.
     */
    public function setField($key, array $source);

    /**
     * Retrieve configuration field.
     *
     * @param $key The contact source data.
     * @return array $fields Contact object.
     */
    public function getField(string $key): array;

}