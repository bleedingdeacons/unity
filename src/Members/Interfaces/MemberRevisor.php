<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Members\ResponderCertification;

/**
 * Member Revisor Interface
 *
 * Builds a modified copy of an existing Member. This is the safe counterpart
 * to {@see MemberFactory::createNew()}, which should only be used to build a
 * genuinely new member.
 *
 * The distinction matters because the two have opposite default semantics, and
 * getting them confused has caused real data loss:
 *
 *   createNew()  omitted parameter => RESET to the type default
 *   revise()     omitted parameter => KEEP the base member's value
 *
 * createNew() takes 23 parameters, 22 of them optional. Every caller that
 * wanted "change one field, keep the rest" had to restate all 23, and a
 * repository that writes every field unconditionally turned any omission into
 * a deletion. That erased members' GDPR consent records on an unrelated REST
 * update and on spreadsheet re-import, and two positional call sites bound
 * their arguments to the wrong parameters entirely and returned 500s.
 *
 * revise() cannot fail that way: a field you do not name is carried over, so
 * the worst case for a forgotten parameter is that nothing changes.
 *
 *     $updated = $container->get(MemberRevisor::class)
 *         ->revise($existing, mobileNumber: '07700900000');
 *
 * Kept separate from {@see MemberFactory} so the two default behaviours stay
 * impossible to confuse at the call site: reach for the factory to create,
 * the revisor to revise.
 */
interface MemberRevisor
{
    /**
     * A copy of $base with the named fields replaced.
     *
     * Every parameter is nullable and defaults to null, meaning "keep the
     * base member's current value". Pass named arguments only.
     *
     * Deliberately not revisable:
     *  - `id`, which identifies the member being revised
     *  - `updated`, derived from the post's modified timestamp on read
     *  - `meetingPO`, which is `mixed`, so null cannot mean "keep" and "set
     *    to null" at once. It is always carried over. The field is already
     *    marked for removal.
     *
     * @param Member                  $base                       The member to revise
     * @param string|null             $anonymousName              Anonymous name
     * @param bool|null               $showAnonymousName          Show anonymous name publicly
     * @param bool|null               $showMemberProfile          Show profile publicly
     * @param string|null             $anonymousProfile           Profile text
     * @param int|null                $intergroupPosition         Position post ID
     * @param string|null             $intergroupPositionRotation Rotation date (Y-m-d)
     * @param int|null                $homeGroup                  Home group post ID
     * @param bool|null               $isGSR                      GSR flag
     * @param string|null             $personalEmail              Personal email address
     * @param string|null             $mobileNumber               Mobile phone number
     * @param bool|null               $twelfthStepper             Available for 12th-step calls
     * @param bool|null               $telephoneResponder         Available as a telephone responder
     * @param ResponderCertification|null $responderCertification Certification stage
     * @param string|null             $area                       Geographic area covered
     * @param array<int, string>|null $accepts                    Forms of contact accepted
     * @param bool|null               $gdprAccepted               Privacy policy accepted
     * @param string|null             $gdprAcceptedAt             Acceptance timestamp (Y-m-d H:i:s)
     * @param string|null             $gdprAcceptanceVersion      Policy version accepted
     * @param string|null             $gdprAcceptanceMethod       How acceptance was captured
     * @param string|null             $gdprAcceptanceStatement    The exact statement accepted
     */
    public function revise(
        Member $base,
        ?string $anonymousName = null,
        ?bool $showAnonymousName = null,
        ?bool $showMemberProfile = null,
        ?string $anonymousProfile = null,
        ?int $intergroupPosition = null,
        ?string $intergroupPositionRotation = null,
        ?int $homeGroup = null,
        ?bool $isGSR = null,
        ?string $personalEmail = null,
        ?string $mobileNumber = null,
        ?bool $twelfthStepper = null,
        ?bool $telephoneResponder = null,
        ?ResponderCertification $responderCertification = null,
        ?string $area = null,
        ?array $accepts = null,
        ?bool $gdprAccepted = null,
        ?string $gdprAcceptedAt = null,
        ?string $gdprAcceptanceVersion = null,
        ?string $gdprAcceptanceMethod = null,
        ?string $gdprAcceptanceStatement = null
    ): Member;
}
