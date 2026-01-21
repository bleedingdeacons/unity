<?php

declare(strict_types=1);

namespace Unity\Members;

use Unity\Members\Interfaces\MemberFactoryInterface;
use Unity\Members\Interfaces\MemberInterface;
use function get_field;
use function get_the_title;

/**
 * Member Factory Implementation
 */
class MemberFactory implements MemberFactoryInterface
{
    /**
     * Create a new Member instance from a WordPress post ID
     *
     * @param int $id WordPress post ID
     * @return MemberInterface
     */
    public function createFromSource(int $id): MemberInterface
    {
        $homeGroupField = get_field(MemberConstants::FIELD_HOME_GROUP, $id);
        $homeGroupId = 0;

        if (is_array($homeGroupField) && !empty($homeGroupField)) {
            // ACF relationship field returns array of post IDs
            $homeGroupId = (int) $homeGroupField[0];
        } elseif (is_numeric($homeGroupField)) {
            $homeGroupId = (int) $homeGroupField;
        }

        return new Member(
            $id,
            get_field(MemberConstants::FIELD_ANONYMOUS_NAME, $id) ?? '',
            get_the_title($id) ?? '',
            get_field(MemberConstants::FIELD_PERSONAL_EMAIL, $id) ?? '',
            (bool) (get_field(MemberConstants::FIELD_SHOW_ANONYMOUS_NAME, $id) ?? false),
            (bool) (get_field(MemberConstants::FIELD_SHOW_MEMBER_PROFILE, $id) ?? false),
            get_field(MemberConstants::FIELD_ANONYMOUS_PROFILE, $id) ?? '',
            (int) (get_field(MemberConstants::FIELD_INTERGROUP_POSITION, $id) ?? 0),
            get_field(MemberConstants::FIELD_INTERGROUP_POSITION_ROTATION, $id) ?? '',
            $homeGroupId,
            (bool) (get_field(MemberConstants::FIELD_HOMEGROUP_GSR, $id) ?? false),
            get_field(MemberConstants::FIELD_MEETING_PO, $id) ?? null,
            get_field(MemberConstants::FIELD_PERSONAL_EMAIL, $id) ?? '',
            get_field(MemberConstants::FIELD_MOBILE_NUMBER, $id) ?? ''
        );
    }
}