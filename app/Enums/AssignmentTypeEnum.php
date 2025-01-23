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
    const REASSIGNED_AS_BOUGHT_LEAD = 6;
    const AssignmentTypeList = [
        self::SYSTEM_ASSIGNED,
        self::SYSTEM_REASSIGNED,
        self::MANUAL_ASSIGNED,
        self::MANUAL_REASSIGNED,
        self::BOUGHT_LEAD,
        self::REASSIGNED_AS_BOUGHT_LEAD,
    ];

    public static function getAssignmentTypeText($assignmentType)
    {
        return getAssignmentTypeText($assignmentType);
    }

    public static function withLabels()
    {
        $item = ['value' => 'all', 'label' => 'All'];
        $items = collect(self::AssignmentTypeList)->map(function ($value) {
            return ['value' => (string) $value, 'label' => self::getAssignmentTypeText($value)];
        })->toArray();

        return array_merge([$item], $items);
    }
}
