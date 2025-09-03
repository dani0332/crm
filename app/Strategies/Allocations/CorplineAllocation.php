<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CorplineAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Business);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorByEmails($onlineStatus, $emails);
        }

        $this->skipRuleUsers = true;

        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::CORPLINE_ADVISORS);

        return $this->getAdvisorByEmails($onlineStatus, $emails);
    }

    public function getAdvisorByEmails($onlineStatus, $emails)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.id', function ($q) {
                $q->select('business_type_of_insurance_user.user_id')->from('business_type_of_insurance_user')->where('business_type_of_insurance_user.business_type_of_insurance_id', $this->lead->business_type_of_insurance_id);
            })
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();
    }
}
