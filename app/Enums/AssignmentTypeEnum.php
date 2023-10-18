<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AssignmentTypeEnum extends Enum
{
    const SYSTEM_ASSIGNED = 1;
    const SYSTEM_REASSIGNED = 2;
    const MANUAL_ASSIGNED = 3;
    const MANUAL_REASSIGNED = 4;
    const AssignmentTypeList = [
        self::SYSTEM_ASSIGNED,
        self::SYSTEM_REASSIGNED,
        self::MANUAL_ASSIGNED,
        self::MANUAL_REASSIGNED,
    ];

    public static function getAssignmentTypeText($assignmentType)
    {
        $assignmentText = '';
        switch ($assignmentType) {
            case 1:
                $assignmentText = 'System Assigned';
                break;
            case 2:
                $assignmentText = 'System ReAssigned';
                break;
            case 3:
                $assignmentText = 'Manual Assigned';
                break;
            case 4:
                $assignmentText = 'Manual ReAssigned';
                break;
            default:
                break;
        }

        return $assignmentText;
    }
}
