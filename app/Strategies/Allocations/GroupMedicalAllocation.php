<?php

namespace App\Strategies\Allocations;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\AllocationConfigurer;
use App\Models\BusinessQuote;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class GroupMedicalAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()
            ->when($this->uuid && ! $this->isEAIMCRMLead(), function ($q) {
                $q->whereNotNull('health_plan_type_id')
                    ->whereNotNull('number_of_employees');
            })
            ->logRawSql()
            ->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        // EA_IMCRM: bypass email-based routing — permission gate in getAdvisorBaseQuery handles filtering
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.'::fetchAdvisor - EA_IMCRM lead detected, bypassing email-based routing and using permission gate');

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::GMAdvisor])
                ->logRawSql()
                ->first();
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, QuoteTypeId::Business);
        if (count($emails) > 0) {
            LoggerService::info(self::class." - Applied rules users for Group Medical Advisors:  | quote Ref-ID: {$this->lead->uuid} ");

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::GMAdvisor])
                ->whereIn('users.email', $emails)
                ->logRawSql()
                ->first();
        }

        if (empty($this->lead->health_plan_type_id)) {
            LoggerService::warning(self::class." - PlanTypeId :{$this->lead->health_plan_type_id} is empty | quote Ref-ID: {$this->lead->uuid} | time: ".now());

            return null;
        }

        if (empty($this->lead->number_of_employees)) {
            LoggerService::warning(self::class." - Number of employees is empty | quote Ref-ID: {$this->lead->uuid} | time: ".now());

            return null;
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getGroupMedicalEligibleAdvisorIds($this->lead);

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::GMAdvisor])
            ->whereIn('users.id', $advisorIds)
            ->logRawSql()
            ->first();
    }

    private function isEAIMCRMLead(): bool
    {
        return BusinessQuote::where('uuid', $this->uuid)
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->exists();
    }
}
