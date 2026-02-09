<?php

declare(strict_types=1);

namespace Unity\Contact\Interfaces;

/**
 * Interface ContactFactoryInterface
 *
 * Defines the contract for creating TsmlContact objects.
 */
interface ContactFactoryInterface
{
    /**
     * Create a TsmlContact object from source data.
     *
     * @param array $source The contact source data.
     * @return ContactInterface TsmlContact object.
     */
    public function createFromSource(array $source): ContactInterface;

    /**
     * Create a TsmlContact object from individual parameters.
     *
     * @param string $name The contact's name.
     * @param string $email The contact's email address.
     * @param string $phone The contact's phone number.
     * @return ContactInterface TsmlContact object.
     */
    public function create(string $name = '', string $email = '', string $phone = ''): ContactInterface;
}
