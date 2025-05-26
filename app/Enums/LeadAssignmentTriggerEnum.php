<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeadAssignmentTriggerEnum extends Enum
{
    const INSTANT_ALFRED = 1;
    const LEAD_AUTO_ASSIGNED = 2;
    const REQUESTED_ADVISOR_ON_WEBSITE = 3;
    const UPFRONT_PAID = 4;
    const REQUEST_PAYMENT_LINK = 5;
    const MANUAL_ALLOCATION = 6;
    const AssignmentTriggerTypeList = [
        self::INSTANT_ALFRED,
        self::LEAD_AUTO_ASSIGNED,
        self::REQUESTED_ADVISOR_ON_WEBSITE,
        self::UPFRONT_PAID,
        self::REQUEST_PAYMENT_LINK,
        self::MANUAL_ALLOCATION,
    ];

    public static function getAssignmentTypeText($assignmentType)
    {
        $options = [
            self::INSTANT_ALFRED => 'Instant Alfred',
            self::LEAD_AUTO_ASSIGNED => 'Lead Auto Assigned',
            self::REQUESTED_ADVISOR_ON_WEBSITE => 'Requested Advisor On Website',
            self::UPFRONT_PAID => 'Upfront Paid',
            self::REQUEST_PAYMENT_LINK => 'Request Payment Link',
            self::MANUAL_ALLOCATION => 'Manual Allocation',
        ];

        return $options[$assignmentType] ?? 'Unknown';
    }

    public static function withLabels()
    {
        $item = ['value' => 'all', 'label' => 'All'];
        $items = collect(self::AssignmentTriggerTypeList)->map(function ($value) {
            return ['value' => $value, 'label' => self::getAssignmentTypeText($value)];
        })->toArray();

        return array_merge([$item], $items);
    }
}
