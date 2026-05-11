<?php

declare(strict_types=1);

namespace Unity\Members\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member Factory Interface
 */
interface MemberFactory
{
    /**
     * Create a Member from a source ID
     *
     * @param int $id WordPress post ID
     * @return Member
     */
    public function createFromSource(int $id): Member;

    /**
     * Create a new Member from imported data without requiring an existing post
     *
     * Used by Reconcile (and other importers) to build a Member object
     * from raw field values. The post is created first via wp_insert_post,
     * then this method wraps the data as a concrete Member ready for
     * MemberRepository::save().
     *
     * @param int    $id                          WordPress post ID (from wp_insert_post)
     * @param string $anonymousName               Anonymous name (e.g. "John D.")
     * @param bool   $showAnonymousName           Whether to display anonymous name publicly
     * @param bool   $showMemberProfile            Whether to display member profile publicly
     * @param string $anonymousProfile             Profile text
     * @param int    $intergroupPosition           Position post ID
     * @param string $intergroupPositionRotation   Rotation date (Y-m-d)
     * @param int    $homeGroup                    Home group post ID
     * @param bool   $isGSR                        GSR flag
     * @param mixed  $meetingPO                    Meeting PO reference
     * @param string $personalEmail                Personal email address
     * @param string $mobileNumber                 Mobile phone number
     * @param bool   $twelfthStepper               Whether the member is available for 12th-step calls
     * @param string             $area           Geographic area covered for 12th-step calls
     * @param array<int, string> $accepts        Forms of contact accepted (checkbox-backed list of option values)
     * @param bool   $gdprAccepted                 Whether the member has accepted the privacy policy
     * @param string $gdprAcceptedAt               GDPR acceptance timestamp (Y-m-d H:i:s) or '' if never accepted
     * @param string $gdprAcceptanceVersion        Version of the policy that was accepted
     * @param string $gdprAcceptanceMethod         How acceptance was captured (e.g. "web-form", "import", "manual")
     * @param string $gdprAcceptanceStatement      The exact statement the member accepted
     * @return Member
     */
    public function createNew(
        int $id,
        string $anonymousName = '',
        bool $showAnonymousName = false,
        bool $showMemberProfile = false,
        string $anonymousProfile = '',
        int $intergroupPosition = 0,
        string $intergroupPositionRotation = '',
        int $homeGroup = 0,
        bool $isGSR = false,
        mixed $meetingPO = null,
        string $personalEmail = '',
        string $mobileNumber = '',
        bool $twelfthStepper = false,
        string $area = '',
        array $accepts = [],
        bool $gdprAccepted = false,
        string $gdprAcceptedAt = '',
        string $gdprAcceptanceVersion = '',
        string $gdprAcceptanceMethod = '',
        string $gdprAcceptanceStatement = ''
    ): Member;

}