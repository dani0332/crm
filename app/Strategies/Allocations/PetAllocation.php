<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;

class PetAllocation extends BaseAllocation
{
    // this function not being used as we overrode fetchAvailableAdvisor, this function exists here just to meet abstract function in parent class
    protected function fetchAdvisor(int $onlineStatus)
    {
        return null;
    }

    private function findEligibleAdvisor(array $statusOrder, $role, $emails = [])
    {
        foreach ($statusOrder as $status) {
            LoggerService::info(self::class." - trying to get {$role} with current status: {$status}");
            $eligibleUser = $this->getAdvisorBaseQuery($status, [$role])->whereIn('users.email', $emails)->first();

            if ($eligibleUser) {
                LoggerService::info(self::class." - eligible {$role} found with status: {$status}, user id: {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        LoggerService::info(self::class." - no eligible {$role} found for the given status order.");

        return null;
    }

    public function fetchAvailableAdvisor($isReassignmentJob = false)
    {
        LoggerService::info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$this->teamId}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);

        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", ['emails' => $emails]);
            if ($advisor = $this->findEligibleAdvisor($statusOrder, RolesEnum::PetAdvisor, $emails)) {
                LoggerService::info(self::class." - eligible pet advisor found with  user id: {$advisor->id}, rule condition: true");

                return $advisor;
            }
        }

        $petAdvisorEmails = $this->getAdvisorEmails(ApplicationStorageEnums::PET_ADVISORS);
        if ($advisor = $this->findEligibleAdvisor($statusOrder, RolesEnum::PetAdvisor, $petAdvisorEmails)) {
            return $advisor;
        }

        // If no pet advisor, find home advisor
        $homeAdvisorEmails = $this->getAdvisorEmails(ApplicationStorageEnums::HOME_ADVISORS_FOR_PET);

        return $this->findEligibleAdvisor($statusOrder, RolesEnum::HomeAdvisor, $homeAdvisorEmails);
    }
}
