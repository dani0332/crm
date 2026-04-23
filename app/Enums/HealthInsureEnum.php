<?php

namespace App\Enums;

enum HealthInsureEnum: string
{
    case ONLY_MYSELF = 'ONLY_MYSELF';
    case ONLY_MY_FAMILY_MEMBERS = 'ONLY_MY_FAMILY_MEMBERS';
    case MYSELF_AND_MY_FAMILY_MEMBERS = 'MYSELF_AND_MY_FAMILY_MEMBERS';

    /** Short label for UI when the lead source is IMCRM; otherwise the lookup row's DB text is shown. */
    public function getLabel(): string
    {
        return match ($this) {
            self::ONLY_MYSELF => 'Only the customer',
            self::ONLY_MY_FAMILY_MEMBERS => "Only the customer's family member(s)",
            self::MYSELF_AND_MY_FAMILY_MEMBERS => 'The customer and their family member(s)',
        };
    }
}
