<?php

namespace App\Strategies\Allocations;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\AllocationConfigurer;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class LifeAllocation extends BaseAllocation
{
    protected function fetchAdvisor(int $onlineStatus)
    {
        $parentLeadAdvisorId = $this->getParentLeadAdvisorId();
        LoggerService::info(self::class.'::fetchAdvisor - Parent lead advisor ID', [
            'parent_lead_advisor_id' => $parentLeadAdvisorId,
        ]);
        LoggerService::info(self::class.'::fetchAdvisor - Starting advisor fetch for Life allocation', [
            'online_status' => $onlineStatus,
        ]);

        // EA_IMCRM: bypass email-based routing — permission gate in getAdvisorBaseQuery handles filtering
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.'::fetchAdvisor - EA_IMCRM lead detected, bypassing email-based routing and using permission gate');

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::LifeAdvisor])
                ->when(
                    $parentLeadAdvisorId !== null,
                    fn ($query) => $query->where('users.id', '!=', $parentLeadAdvisorId)
                )
                ->first();
        }

        $emails = $this->getAdvisorEmails();

        LoggerService::info(self::class.'::fetchAdvisor - Querying advisors with emails', [
            'online_status' => $onlineStatus,
            'email_count' => count($emails),
            'emails' => $emails ?? [],
        ]);

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::LifeAdvisor])
            ->whereIn('users.email', $emails)
            ->when(
                $parentLeadAdvisorId !== null,
                fn ($query) => $query->where('users.id', '!=', $parentLeadAdvisorId)
            )
            ->logRawSql()
            ->first();
    }

    protected function getAdvisorEmails($storageKey = null)
    {
        LoggerService::info(self::class.'::getAdvisorEmails - Starting advisor email resolution', [
            'lead_source' => $this->lead->source ?? 'unknown',
        ]);

        if ($this->lead->isFIC(QuoteTypes::LIFE)) {
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule users');
            $users = $this->getFicRulesUsers();
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule  ', [
                'users_ids' => $users ? $users->pluck('id')->toArray() : [],
            ]);

            return $users->pluck('email')->toArray();
        }

        LoggerService::info(self::class.'::getAdvisorEmails - Checking lead source rules', [
            'lead_source' => $this->lead->source ?? 'unknown',
        ]);

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", [
                'emails' => $emails ?? [],
            ]);

            return $emails;
        }

        $this->skipRuleUsers = true;

        LoggerService::info(self::class.'::getAdvisorEmails - Lead is not FIC, fetching eligible advisor ids', [
            'sum_insured_value' => $this->lead->lifeQuote?->sum_insured_value ?? null,
        ]);
        $advisorIds = AllocationConfigurer::getLifeEligibleAdvisorIds($this->lead);

        LoggerService::info(self::class.'::getAdvisorEmails - Lead is not FIC, eligible advisor ids fetched', [
            'advisor_ids' => $advisorIds ?? [],
        ]);

        return User::whereIn('id', $advisorIds)->pluck('email')->toArray();
    }

    private function getFicRulesUsers()
    {
        LoggerService::info(self::class.'::getFicRulesUsers - Fetching FIC rule user IDs');

        $usersIds = app(RuleService::class)->getFicRulesUsers(QuoteTypes::LIFE);

        LoggerService::info(self::class.'::getFicRulesUsers - FIC rule user IDs retrieved', [
            'user_id_count' => count($usersIds),
            'user_ids' => $usersIds ?? [],
        ]);

        return User::select('id', 'email')->whereIn('id', $usersIds)->get();
    }
}
