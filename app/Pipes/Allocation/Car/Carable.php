<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LeadAllocation;

trait Carable
{
    public function getBaseQuery($status, $userIds)
    {
        $excludedUserIds = $this->allocationRequest->get('excludedUserIds');

        return LeadAllocation::whereHas('leadAllocationUser', function ($query) use ($status) {
            $query->where('status', $status)->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', [RolesEnum::CLIENTSUPPORT, RolesEnum::CLIENTSUPPORTLEAD]);
            });
        })
            ->whereIn('user_id', $userIds)
            ->when(! empty($excludedUserIds), function ($query) use ($excludedUserIds) {
                $query->whereNotIn('user_id', $excludedUserIds);
            })
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->when(
                $this->allocationRequest->hasNationalityConfig(),
                fn ($q) => $q->whereIn('user_id', $this->allocationRequest->getAdvisorIDs()),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('user_id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->when($this->allocationRequest->getReAssigFromAdvisorId(), fn ($q) => $q->where('user_id', '!=', $this->allocationRequest->getReAssigFromAdvisorId()));
    }
}
