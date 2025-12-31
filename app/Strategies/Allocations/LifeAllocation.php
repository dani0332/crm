<?php

namespace App\Strategies\Allocations;

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
        $emails = $this->getAdvisorEmails();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::LifeAdvisor])
            ->whereIn('users.email', $emails)
            ->logRawSql()
            ->first();
    }

    protected function getAdvisorEmails($storageKey = null)
    {
        if ($this->lead->isFIC(QuoteTypes::LIFE)) {
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule users');
            $users = $this->getFicRulesUsers();
            LoggerService::info(self::class.'::getAdvisorEmails - Lead is FIC, fetching FIC rule  ', ['users_ids' => $users->pluck('id')->toArray()]);

            return $users->pluck('email')->toArray();
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);

            return $emails;
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getLifeEligibleAdvisorIds($this->lead);

        return User::whereIn('id', $advisorIds)->pluck('email')->toArray();
    }

    private function getFicRulesUsers()
    {
        $usersIds = app(RuleService::class)->getFicRulesUsers();

        return User::select('id', 'email')->whereIn('id', $usersIds)->get();
    }
}
