<?php

namespace App\Strategies\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Services\AllocationConfigurationService;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class CyberAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Cyber);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CyberAdvisor, RolesEnum::CyberManager])
                ->whereIn('users.email', $emails)
                ->logRawSql()
                ->first();
        }

        $this->skipRuleUsers = true;

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CyberAdvisor, RolesEnum::CyberManager])
            ->logRawSql()
            ->first();
    }
}













