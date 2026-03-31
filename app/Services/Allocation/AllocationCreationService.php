<?php

namespace App\Services\Allocation;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\LifeQuote;

class AllocationCreationService
{
    public function executeLifeRevivalAllocation()
    {
        // Step 1: Get all LIFE quotes eligible for revival
        $leadsToRevive = LifeQuote::whereNot('source', LeadSourceEnum::REVIVAL)
            ->whereDate('created_at', '<=', now()->subDays(90))
            ->select('uuid', 'dob', 'gender', 'quote_status_id')
            ->get();

        // Step 2: Filter duplicate insured
        $filteredLeads = $this->filterDuplicateInsured($leadsToRevive);
    }

    private function filterDuplicateInsured($leads)
    {
        // Step 2: Group quotes by dob and gender
        $grouped = $leads->groupBy(function ($item) {
            return $item->dob.'_'.$item->gender;
        });

        // Step 3: Exclude group if any quote in the group is PolicyBooked
        $filteredLeads = collect();
        foreach ($grouped as $group) {
            // Add since its not a duplicate
            if ($group->count() < 2) {
                $filteredLeads = $filteredLeads->merge($group);

                continue;
            }

            // If any quote in this group is PolicyBooked, skip the whole group
            if ($group->contains(function ($item) {
                return $item->quote_status_id == QuoteStatusEnum::PolicyBooked;
            })) {
                continue;
            }
            // Add all non-policybooked leads
            $filteredLeads = $filteredLeads->merge($group);
        }

        // Exclude policybooked individual leads (since above we filetered only group)
        $filteredLeads = $filteredLeads->filter(function ($item) {
            return $item->quote_status_id != QuoteStatusEnum::PolicyBooked;
        });

        return $filteredLeads;
    }
}
