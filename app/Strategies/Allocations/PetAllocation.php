<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;

class PetAllocation extends BaseAllocation
{
    protected function resolveLead(): void
    {
        $this->lead = $this->quoteType->model()
            ->where('uuid', $this->uuid)
            ->where('quote_type_id', $this->quoteType->id())
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->when(! $this->overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'))
            ->first();
    }

    private function getAdvisor(int $onlineStatus, $role = RolesEnum::PetAdvisor)
    {
        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', -1);
            })
            ->whereIn('r.name', [$role])
            ->where('la.quote_type_id', $this->quoteType->id())
            ->where('users.is_active', true)
            ->orderBy('la.last_allocated', 'asc')
            ->first();
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
