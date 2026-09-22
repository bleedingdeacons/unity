<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Members;

use Unity\Members\ResponderCertification;

/*
 * Tests for the {@see ResponderCertification} enum: the ACF-value coercion
 * that read paths rely on, the deliberately narrow "certified" check, and the
 * admin label.
 */

it('resolves a known ACF value to its case', function () {
    expect(ResponderCertification::fromAcfValue('Certified'))->toBe(ResponderCertification::Certified)
        ->and(ResponderCertification::fromAcfValue('In Training'))->toBe(ResponderCertification::InTraining);
});

it('falls back to none for unusable values', function (mixed $value) {
    expect(ResponderCertification::fromAcfValue($value))->toBe(ResponderCertification::None);
})->with([
    'unknown string' => ['Retired'],
    'empty string'   => [''],
    'null'           => [null],
    'false'          => [false],
    'array'          => [['Certified']],
    'int'            => [3],
]);

it('counts only certified as certified', function () {
    expect(ResponderCertification::Certified->isCertified())->toBeTrue();

    foreach (
        [
        ResponderCertification::None,
        ResponderCertification::Applied,
        ResponderCertification::InTraining,
        ResponderCertification::Pending,
        ] as $stage
    ) {
        expect($stage->isCertified())->toBeFalse($stage->name . ' must not count as certified');
    }
});

it('labels each case with its backing value', function () {
    expect(ResponderCertification::Certified->label())->toBe('Certified')
        ->and(ResponderCertification::InTraining->label())->toBe('In Training');
});
