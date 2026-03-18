<?php

declare(strict_types=1);

namespace Unity\Contacts\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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

    /**
     * Get the last updated timestamp.
     *
     * @return string Last updated datetime string.
     */
    public function getUpdated(): string;
}
