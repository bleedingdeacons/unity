<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Positions;

use PHPUnit\Framework\TestCase;
use Unity\Positions\Position;

/**
 * Tests for Position entity
 */
class PositionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_with_default_values(): void
    {
        $position = new Position();

        $this->assertEquals(0, $position->getId());
        $this->assertEquals(6, $position->getMinimumSobriety());
        $this->assertEquals(1, $position->getTermYears());
        $this->assertEquals('', $position->getEmail());
        $this->assertEquals('', $position->getLongName());
        $this->assertEquals('', $position->getShortDescription());
        $this->assertEquals('', $position->getSummary());
        $this->assertEquals('', $position->getLink());
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_with_all_values(): void
    {
        $position = new Position(
            id: 10,
            minimumSobriety: 12,
            termYears: 2,
            email: 'secretary@example.com',
            longName: 'General Secretary',
            shortDescription: 'Handles administrative duties',
            summary: 'The General Secretary is responsible for...',
            link: 'https://example.com/positions/secretary'
        );

        $this->assertEquals(10, $position->getId());
        $this->assertEquals(12, $position->getMinimumSobriety());
        $this->assertEquals(2, $position->getTermYears());
        $this->assertEquals('secretary@example.com', $position->getEmail());
        $this->assertEquals('General Secretary', $position->getLongName());
        $this->assertEquals('Handles administrative duties', $position->getShortDescription());
        $this->assertEquals('The General Secretary is responsible for...', $position->getSummary());
        $this->assertEquals('https://example.com/positions/secretary', $position->getLink());
    }

    /**
     * @test
     */
    public function it_is_valid_with_all_required_fields(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertTrue($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_id_is_zero(): void
    {
        $position = new Position(
            id: 0,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_email_is_empty(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: '',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_long_name_is_empty(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: '',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_short_description_is_empty(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: '',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_summary_is_empty(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: ''
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_minimum_sobriety_is_less_than_six(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 5,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_invalid_when_term_years_is_less_than_one(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 0,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary'
        );

        $this->assertFalse($position->isValid());
    }

    /**
     * @test
     */
    public function it_is_valid_with_higher_sobriety_requirement(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 24,
            termYears: 2,
            email: 'chair@example.com',
            longName: 'Chairperson',
            shortDescription: 'Leads the group',
            summary: 'Responsible for chairing meetings'
        );

        $this->assertTrue($position->isValid());
        $this->assertEquals(24, $position->getMinimumSobriety());
    }

    /**
     * @test
     */
    public function it_does_not_require_link_for_validity(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Position Name',
            shortDescription: 'Short description',
            summary: 'Position summary',
            link: ''
        );

        $this->assertTrue($position->isValid());
        $this->assertEquals('', $position->getLink());
    }

    /**
     * @test
     */
    public function it_accepts_exactly_six_months_sobriety(): void
    {
        $position = new Position(
            id: 1,
            minimumSobriety: 6,
            termYears: 1,
            email: 'position@example.com',
            longName: 'Entry Position',
            shortDescription: 'Entry level service position',
            summary: 'Good for newcomers to service'
        );

        $this->assertTrue($position->isValid());
        $this->assertEquals(6, $position->getMinimumSobriety());
    }
}
