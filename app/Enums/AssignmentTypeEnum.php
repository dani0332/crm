<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AssignmentTypeEnum extends Enum
{
    const SYSTEM_ASSIGNED = 1;
    const SYSTEM_REASSIGNED = 2;
    const MANUAL_ASSIGNED = 3;
    const MANUAL_REASSIGNED = 4;
    const BOUGHT_LEAD = 5;
    const REASSIGNED_TO_BOUGHT_LEAD = 6;
    const AssignmentTypeList = [
        self::SYSTEM_ASSIGNED,
        self::SYSTEM_REASSIGNED,
        self::MANUAL_ASSIGNED,
        self::MANUAL_REASSIGNED,
        self::BOUGHT_LEAD,
        self::REASSIGNED_TO_BOUGHT_LEAD,
    ];

    public static function getAssignmentTypeText($assignmentType)
    {
        return getAssignmentTypeText($assignmentType);
    }
}
