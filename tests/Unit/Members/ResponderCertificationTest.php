<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use PHPUnit\Framework\TestCase;
use Unity\Members\ResponderCertification;

/**
 * Tests for the {@see ResponderCertification} enum: the ACF-value coercion
 * that read paths rely on, the deliberately narrow "certified" check, and the
 * admin label.
 */
class ResponderCertificationTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_a_known_acf_value_to_its_case(): void
    {
        $this->assertSame(ResponderCertification::Certified, ResponderCertification::fromAcfValue('Certified'));
        $this->assertSame(ResponderCertification::InTraining, ResponderCertification::fromAcfValue('In Training'));
    }

    /**
     * @test
     * @dataProvider nonResolvingValues
     */
    public function it_falls_back_to_none_for_unusable_values(mixed $value): void
    {
        $this->assertSame(ResponderCertification::None, ResponderCertification::fromAcfValue($value));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function nonResolvingValues(): array
    {
        return [
            'unknown string' => ['Retired'],
            'empty string'   => [''],
            'null'           => [null],
            'false'          => [false],
            'array'          => [['Certified']],
            'int'            => [3],
        ];
    }

    /**
     * @test
     */
    public function only_certified_counts_as_certified(): void
    {
        $this->assertTrue(ResponderCertification::Certified->isCertified());

        foreach ([
            ResponderCertification::None,
            ResponderCertification::Applied,
            ResponderCertification::InTraining,
            ResponderCertification::Pending,
        ] as $stage) {
            $this->assertFalse($stage->isCertified(), $stage->name . ' must not count as certified');
        }
    }

    /**
     * @test
     */
    public function label_returns_the_backing_value(): void
    {
        $this->assertSame('Certified', ResponderCertification::Certified->label());
        $this->assertSame('In Training', ResponderCertification::InTraining->label());
    }
}
