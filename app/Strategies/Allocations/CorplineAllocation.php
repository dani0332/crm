<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;

class CorplineAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CORPLINE_ADVISORS);

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.id', function ($q) {
                $q->select('business_type_of_insurance_user.user_id')->from('business_type_of_insurance_user')->where('business_type_of_insurance_user.business_type_of_insurance_id', $this->lead->business_type_of_insurance_id);
            })
            ->whereIn('users.email', $emails)
            ->first();
    }
}
