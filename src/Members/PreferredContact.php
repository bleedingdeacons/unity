<?php

declare(strict_types=1);

namespace Unity\Members;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Which of a member's two numbers should be rung.
 *
 * Backed by the `responder-preferred-contact` ACF radio field, whose
 * conditional logic shows it only when
 * {@see Interfaces\Member::getLandlineNumber()} holds a value. The case
 * values are the ACF choice values verbatim — changing one here without
 * changing the field export (and migrating the stored postmeta) will make
 * every existing member fall back to {@see self::Mobile}.
 *
 * A member with no landline is {@see self::Mobile}, and that is an
 * invariant rather than merely a default: there is only one number to
 * ring, so any other answer would be a call placed to nothing. ACF stores
 * nothing for a conditionally hidden field, so for a member who never had
 * a landline the fallback settles it — but a member who *had* one and had
 * it deleted keeps whatever was last saved, which may well say Landline.
 * {@see self::resolve()} exists for that case and is what every read and
 * write goes through; {@see self::fromAcfValue()} on its own does not know
 * about the landline and cannot enforce it.
 */
enum PreferredContact: string
{
    case Mobile = 'Mobile';
    case Landline = 'Landline';

    /**
     * Resolve a raw ACF value to a case, falling back to {@see self::Mobile}.
     *
     * ACF returns null/false/'' for a field that has never been saved or is
     * currently hidden by conditional logic, and a stale string if a choice
     * is later renamed in the field group. Neither is worth a TypeError on
     * a read path, so both degrade to Mobile.
     *
     * Knows nothing about the landline: prefer {@see self::resolve()}, which
     * does.
     *
     * @param mixed $value Raw value as returned by get_field()
     */
    public static function fromAcfValue(mixed $value): self
    {
        return is_string($value)
            ? (self::tryFrom($value) ?? self::Mobile)
            : self::Mobile;
    }

    /**
     * The preference a member with this landline actually has.
     *
     * The single place the "no landline means Mobile" rule is expressed, so
     * that reading a member back and writing one out cannot disagree about
     * it. Everything else defers to {@see self::fromAcfValue()}.
     *
     * @param mixed  $value          Raw value as returned by get_field()
     * @param string $landlineNumber The member's landline, empty when none
     */
    public static function resolve(mixed $value, string $landlineNumber): self
    {
        if (trim($landlineNumber) === '') {
            return self::Mobile;
        }

        return self::fromAcfValue($value);
    }

    /**
     * Human-readable label for admin screens and REST payloads.
     */
    public function label(): string
    {
        return $this->value;
    }
}
