<?php

declare(strict_types=1);

namespace Unity\Contacts\Interfaces;

/**
 * Interface ContactInterface
 *
 * Defines the contract for TsmlContact objects.
 */
interface Contact
{
    /**
     * Get contact name.
     *
     * @return string TsmlContact name.
     */
    public function getName(): string;

    /**
     * Get contact email.
     *
     * @return string TsmlContact email.
     */
    public function getEmail(): string;

    /**
     * Get contact phone.
     *
     * @return string TsmlContact phone.
     */
    public function getPhone(): string;
}
