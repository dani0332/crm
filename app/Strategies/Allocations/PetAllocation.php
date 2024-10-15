<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;

class PetAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()
            ->where('quote_type_id', $this->quoteType->id())
            ->first();
    }

    private function getAdvisor(int $onlineStatus, $role = RolesEnum::PetAdvisor)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, [$role])->first();
    }

    protected function fetchAdvisor(int $onlineStatus)
    {
        return $this->getAdvisor($onlineStatus);
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

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} for lead uuid: {$this->uuid}");
            $eligibleUser = $this->fetchAdvisor($status);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$this->uuid}");

                return User::find($eligibleUser->user_id);
            }
        }

        // if pet advisor not found, then find home advisor
        foreach ($statusOrder as $status) {
            info(self::class." - trying to get home advisors with current status as {$status} for lead uuid: {$this->uuid}");
            $eligibleUser = $this->getAdvisor($status, RolesEnum::HomeAdvisor);

            if ($eligibleUser) {
                info(self::class." - eligible home advisor user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$this->uuid}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }
}
