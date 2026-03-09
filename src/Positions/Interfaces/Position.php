<?php

declare(strict_types=1);

namespace Unity\Positions\Interfaces;

/**
 * Interface for Position entity
 */
interface Position
{
    /**
     * Get the ID of the position
     * 
     * @return int The position ID
     */
    public function getId(): int;

    /**
     * Get the minimum sobriety requirement in months
     * 
     * @return int Minimum sobriety in months
     */
    public function getMinimumSobriety(): int;

    /**
     * Get the term length in years
     * 
     * @return int Term length in years
     */
    public function getTermYears(): int;

    /**
     * Get the position's email address
     * 
     * @return string Email address
     */
    public function getEmail(): string;

    /**
     * Get the position's full name
     * 
     * @return string Full position name
     */
    public function getLongName(): string;

    /**
     * Get the position's short description
     * 
     * @return string Short description
     */
    public function getShortDescription(): string;

    /**
     * Get the position's summary
     * 
     * @return string Summary
     */
    public function getSummary(): string;

    /**
     * Get the position's link
     * 
     * @return string Link URL
     */
    public function getLink(): string;

    /**
     * Check if the current position is valid
     * 
     * @return bool Whether the position is valid
     */
    public function isValid(): bool;

    /**
     * Get the last updated timestamp
     * 
     * @return string Last updated datetime string
     */
    public function getUpdated(): string;
}
