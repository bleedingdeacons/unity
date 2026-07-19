<?php

declare(strict_types=1);

namespace Unity\Members;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Where a telephone responder has got to in the certification process.
 *
 * Backed by the `responder-certification` ACF radio field, which is only
 * shown when {@see Interfaces\Member::isTelephoneResponder()} is set. The
 * case values are the ACF choice values verbatim — changing one here
 * without changing the field export (and migrating the stored postmeta)
 * will make every existing member fall back to {@see self::None}.
 *
 * A member who is not a telephone responder is {@see self::None}: ACF
 * stores nothing for a conditionally hidden field, so absent and
 * "not started" are the same state.
 */
enum ResponderCertification: string
{
    case None = 'None';
    case Applied = 'Applied';
    case InTraining = 'In Training';
    case CertificationPending = 'Certification Pending';
    case Certified = 'Certified';
    case RecertificationRequired = 'Recertification Required';
    case Denied = 'Denied';

    /**
     * Resolve a raw ACF value to a case, falling back to {@see self::None}.
     *
     * ACF returns null/false/'' for a field that has never been saved or is
     * currently hidden by conditional logic, and a stale string if a choice
     * is later renamed in the field group. Neither is worth a TypeError on
     * a read path, so both degrade to None.
     *
     * @param mixed $value Raw value as returned by get_field()
     */
    public static function fromAcfValue(mixed $value): self
    {
        return is_string($value)
            ? (self::tryFrom($value) ?? self::None)
            : self::None;
    }

    /**
     * Whether the member holds a current certification.
     *
     * Deliberately narrow: only {@see self::Certified} qualifies.
     * Recertification Required means the certification has lapsed.
     */
    public function isCertified(): bool
    {
        return $this === self::Certified;
    }

    /**
     * Human-readable label for admin screens and REST payloads.
     */
    public function label(): string
    {
        return $this->value;
    }
}
