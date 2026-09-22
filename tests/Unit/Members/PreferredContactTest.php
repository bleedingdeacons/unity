<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use Unity\Members\PreferredContact;

/*
 * Tests for the {@see PreferredContact} enum: the ACF-value coercion that read
 * paths rely on, the landline invariant that resolve() is the sole home of,
 * and the admin label.
 */

it('resolves a known ACF value to its case', function () {
    expect(PreferredContact::fromAcfValue('Mobile'))->toBe(PreferredContact::Mobile)
        ->and(PreferredContact::fromAcfValue('Landline'))->toBe(PreferredContact::Landline);
});

it('falls back to mobile for unusable values', function (mixed $value) {
    expect(PreferredContact::fromAcfValue($value))->toBe(PreferredContact::Mobile);
})->with([
    'never saved'      => [null],
    'hidden by acf'    => [false],
    'empty string'     => [''],
    'renamed choice'   => ['Home Phone'],
    'wrong case'       => ['landline'],
    'not a string'     => [42],
    'array'            => [['Landline']],
]);

// A member with no landline has one number, so there is nothing to
// choose between. This is the rule the whole enum exists to hold, and
// resolve() is the only place it lives.
//
// The first case is the one that matters: ACF keeps whatever was last
// saved when a field is hidden again, so a member who had a landline and
// lost it still has 'Landline' in postmeta. fromAcfValue() takes that at
// face value; resolve() is what stops a call being placed to nothing.
it('always makes a member with no landline mobile', function () {
    expect(PreferredContact::resolve('Landline', ''))->toBe(PreferredContact::Mobile)
        ->and(PreferredContact::resolve('Mobile', ''))->toBe(PreferredContact::Mobile)
        ->and(PreferredContact::resolve(null, ''))->toBe(PreferredContact::Mobile);
});

// A number typed as spaces is no number, and ACF stores exactly what
// was typed.
it('does not count a whitespace-only landline as a number', function () {
    expect(PreferredContact::resolve('Landline', '   '))->toBe(PreferredContact::Mobile);
});

it('keeps the stored choice of a member with a landline', function () {
    expect(PreferredContact::resolve('Landline', '0117 496 0000'))->toBe(PreferredContact::Landline)
        ->and(PreferredContact::resolve('Mobile', '0117 496 0000'))->toBe(PreferredContact::Mobile);
});

// Having a landline does not on its own make it preferred: an unsaved
// field still resolves to Mobile, which is the ACF field's own default.
it('does not promote a landline on its own', function () {
    expect(PreferredContact::resolve(null, '0117 496 0000'))->toBe(PreferredContact::Mobile);
});

it('labels each case with its stored value', function () {
    expect(PreferredContact::Mobile->label())->toBe('Mobile')
        ->and(PreferredContact::Landline->label())->toBe('Landline');
});

// The case values are the ACF choice values verbatim. Changing one
// without migrating the stored postmeta sends every existing member
// back to Mobile, so pin them.
it('has case values matching the ACF field export', function () {
    expect(PreferredContact::Mobile->value)->toBe('Mobile')
        ->and(PreferredContact::Landline->value)->toBe('Landline')
        ->and(PreferredContact::cases())->toHaveCount(2);
});
