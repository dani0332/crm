<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Services\RuleService;
use App\Models\User;
use App\Services\Logger\LoggerService;

class CorplineAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CORPLINE_ADVISORS);
        $rules = app(RuleService::class)->getUsersByLeadSourceRules($this->lead->source, $this->lead->quote_type_id);
        if(count($rules) > 0){
            $userIds = app(RuleService::class)->getUserIdsFromRuleRecords($rules);
            $emails = User::whereIn('id', $userIds)->pluck('email')->toArray();
            LoggerService::info(self::class." - Applied rules users for CorpLine Advisors: ".implode(',', $userIds)." | quote Ref-ID: {$this->lead->uuid} ");
            return $emails;
        }
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.id', function ($q) {
                $q->select('business_type_of_insurance_user.user_id')->from('business_type_of_insurance_user')->where('business_type_of_insurance_user.business_type_of_insurance_id', $this->lead->business_type_of_insurance_id);
            })
            ->whereIn('users.email', $emails)
            ->first();
    }
}
