<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;

class PetAllocation extends BaseAllocation
{
    // this function not being used as we overrode fetchAvailableAdvisor, this function exists here just to meet abstract function in parent class
    protected function fetchAdvisor(int $onlineStatus)
    {
        return null;
    }

    private function findEligibleAdvisor(array $statusOrder, $role)
    {
        foreach ($statusOrder as $status) {
            info(self::class." - trying to get {$role} with current status: {$status} for lead uuid: {$this->uuid}");
            $eligibleUser = $this->getAdvisorBaseQuery($status, [$role])->first();

            if ($eligibleUser) {
                info(self::class." - eligible {$role} found with status: {$status}, user id: {$eligibleUser->user_id}, and uuid: {$this->uuid}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    public function fetchAvailableAdvisor($isReassignmentJob = false)
    {
        info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$this->teamId} - {$this->uuid}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        if ($advisor = $this->findEligibleAdvisor($statusOrder, RolesEnum::PetAdvisor)) {
            return $advisor;
        }

        // If no pet advisor, find home advisor
        return $this->findEligibleAdvisor($statusOrder, RolesEnum::HomeAdvisor);
    }
}
