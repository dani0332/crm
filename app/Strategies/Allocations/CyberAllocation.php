<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;

class CyberAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CyberAdvisor], $skipMaxCapCheck = true)
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();
    }

    protected function getAdvisorEmails($storageKey = null)
    {

        $Smitha = 'smitha.chandran@insurancemarket.ae';
        $Neil = 'neil.rama@insurancemarket.ae';
        $fahad = 'fahadhussain2020@gmail.com';

        $emails = [];

        // $isOnLeave = $this->isUserOnLeave($fahad, addUnavailable: true);

        // if ($isOnLeave) {
        //     // if Smitha is on leave, then assign lead to Neil
        //     $emails = [$Neil];
        // } else {
        //     // by default, every cyber lead will be assigned to Smitha
        //     $emails = [$Smitha];
        // }

        $emails = [$fahad];

        $this->skipRuleUsers = true;

        return $emails;
    }
}













