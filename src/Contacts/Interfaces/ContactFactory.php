<?php

declare(strict_types=1);

namespace Unity\Contacts\Interfaces;

/**
 * Interface ContactFactory
 *
 * Defines the contract for creating Contact objects.
 */
interface ContactFactory
{
    /**
     * Create a Contact object from source data.
     *
     * @param array $source The contact source data.
     * @return Contact Contact object.
     */
    public function createFromSource(array $source): Contact;

    /**
     * Create a Contact object from individual parameters.
     *
     * @param string $name The contact's name.
     * @param string $email The contact's email address.
     * @param string $phone The contact's phone number.
     * @return Contact Interface object.
     */
    public function create(string $name = '', string $email = '', string $phone = ''): Contact;
}
