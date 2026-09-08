<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use PHPUnit\Framework\TestCase;
use Unity\Members\PreferredContact;

/**
 * Tests for the {@see PreferredContact} enum: the ACF-value coercion that read
 * paths rely on, the landline invariant that resolve() is the sole home of,
 * and the admin label.
 */
class PreferredContactTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_a_known_acf_value_to_its_case(): void
    {
        $this->assertSame(PreferredContact::Mobile, PreferredContact::fromAcfValue('Mobile'));
        $this->assertSame(PreferredContact::Landline, PreferredContact::fromAcfValue('Landline'));
    }

    /**
     * @test
     * @dataProvider nonResolvingValues
     */
    public function it_falls_back_to_mobile_for_unusable_values(mixed $value): void
    {
        $this->assertSame(PreferredContact::Mobile, PreferredContact::fromAcfValue($value));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function nonResolvingValues(): array
    {
        return [
            'never saved'      => [null],
            'hidden by acf'    => [false],
            'empty string'     => [''],
            'renamed choice'   => ['Home Phone'],
            'wrong case'       => ['landline'],
            'not a string'     => [42],
            'array'            => [['Landline']],
        ];
    }

    /**
     * A member with no landline has one number, so there is nothing to
     * choose between. This is the rule the whole enum exists to hold, and
     * resolve() is the only place it lives.
     *
     * The first case is the one that matters: ACF keeps whatever was last
     * saved when a field is hidden again, so a member who had a landline and
     * lost it still has 'Landline' in postmeta. fromAcfValue() takes that at
     * face value; resolve() is what stops a call being placed to nothing.
     *
     * @test
     */
    public function a_member_with_no_landline_is_always_mobile(): void
    {
        $this->assertSame(PreferredContact::Mobile, PreferredContact::resolve('Landline', ''));
        $this->assertSame(PreferredContact::Mobile, PreferredContact::resolve('Mobile', ''));
        $this->assertSame(PreferredContact::Mobile, PreferredContact::resolve(null, ''));
    }

    /**
     * A number typed as spaces is no number, and ACF stores exactly what
     * was typed.
     *
     * @test
     */
    public function a_whitespace_only_landline_does_not_count_as_a_number(): void
    {
        $this->assertSame(PreferredContact::Mobile, PreferredContact::resolve('Landline', '   '));
    }

    /**
     * @test
     */
    public function a_member_with_a_landline_keeps_their_stored_choice(): void
    {
        $this->assertSame(
            PreferredContact::Landline,
            PreferredContact::resolve('Landline', '0117 496 0000')
        );
        $this->assertSame(
            PreferredContact::Mobile,
            PreferredContact::resolve('Mobile', '0117 496 0000')
        );
    }

    /**
     * Having a landline does not on its own make it preferred: an unsaved
     * field still resolves to Mobile, which is the ACF field's own default.
     *
     * @test
     */
    public function having_a_landline_does_not_promote_it_on_its_own(): void
    {
        $this->assertSame(PreferredContact::Mobile, PreferredContact::resolve(null, '0117 496 0000'));
    }

    /**
     * @test
     */
    public function it_labels_each_case_with_its_stored_value(): void
    {
        $this->assertSame('Mobile', PreferredContact::Mobile->label());
        $this->assertSame('Landline', PreferredContact::Landline->label());
    }

    /**
     * The case values are the ACF choice values verbatim. Changing one
     * without migrating the stored postmeta sends every existing member
     * back to Mobile, so pin them.
     *
     * @test
     */
    public function the_case_values_match_the_acf_field_export(): void
    {
        $this->assertSame('Mobile', PreferredContact::Mobile->value);
        $this->assertSame('Landline', PreferredContact::Landline->value);
        $this->assertCount(2, PreferredContact::cases());
    }
}
