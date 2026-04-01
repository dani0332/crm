<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\AllocationConfigurer;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CorplineAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        if ($this->lead->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::POLITICAL_VIOLENCE_AND_TERRORISM_INSURANCE) {
            return $this->fetchPoliticalViolenceAdvisor($onlineStatus);
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Business);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorByEmails($onlineStatus, $emails);
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getCorplineEligibleAdvisorIds($this->lead);
        $emails = User::whereIn('id', $advisorIds)->pluck('email')->toArray();

        return $this->getAdvisorByEmails($onlineStatus, $emails);
    }

    private function fetchPoliticalViolenceAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails(ApplicationStorageEnums::POLITICAL_VIOLENCE_ADVISOR_EMAIL);
        $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.email', $emails)
            ->first();

        if (empty($advisor)) {
            LoggerService::warning(self::class." - No Political Violence & Terrorism advisor email configured | quote Ref-ID: {$this->lead->uuid}");

            return null;
        }

        LoggerService::info(self::class." - Fetching Political Violence & Terrorism advisor | quote Ref-ID: {$this->lead->uuid}", ['emails' => $emails]);

        return $advisor;
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
