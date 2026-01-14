<?php

declare(strict_types=1);

namespace Unity\Contact\Interfaces;

/**
 * Interface ContactInterface
 *
 * Defines the contract for Contact objects.
 */
interface ContactInterface
{
    /**
     * Get contact name.
     *
     * @return string Contact name.
     */
    public function getName(): string;

    /**
     * Get contact email.
     *
     * @return string Contact email.
     */
    public function getEmail(): string;

    /**
     * Get contact phone.
     *
     * @return string Contact phone.
     */
    public function getPhone(): string;
}
