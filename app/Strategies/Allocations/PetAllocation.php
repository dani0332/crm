<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;

class PetAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()->first();
    }

    private function getAdvisor(int $onlineStatus, $role = RolesEnum::PetAdvisor)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [$role])->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisor($onlineStatus);
    }

    private function findEligibleAdvisor(array $statusOrder, $role)
    {
        foreach ($statusOrder as $status) {
            info(self::class." - trying to get {$role} with current status: {$status} for lead uuid: {$this->uuid}");
            $eligibleUser = $this->getAdvisor($status, $role);

            if ($eligibleUser) {
                info(self::class." - eligible {$role} found with status: {$status}, user id: {$eligibleUser->user_id}, and uuid: {$this->uuid}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    protected function fetchAvailableAdvisor($isReassignmentJob = false)
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
