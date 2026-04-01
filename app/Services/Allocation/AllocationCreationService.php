<?php

namespace App\Services\Allocation;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\LifeQuote;
use Illuminate\Support\Collection;

class AllocationCreationService
{
    public function executeLifeRevivalAllocation()
    {
        // Step 1: Get all LIFE quotes eligible for revival
        $leadsToRevive = LifeQuote::whereNot('source', LeadSourceEnum::REVIVAL)
            ->whereDate('created_at', '<=', now()->subDays(90))
            ->select('uuid','email', 'mobile_no', 'dob', 'gender', 'quote_status_id')
            ->get();

        // Step 2: Filter duplicate insured
        $filteredLeads = $this->filterDuplicateInsured($leadsToRevive);

        // Step 3: Different insured with same contact
        $differentInsuredWithSameContact = $this->filterDifferentInsuredWithSameContact($filteredLeads);
        echo count($differentInsuredWithSameContact); exit;
    }

    private function filterDuplicateInsured(Collection $leads): Collection
    {
        // Step 2: Group quotes by dob and gender
        $grouped = $leads->groupBy(function ($lead) {
            return $lead->dob.'_'.$lead->gender;
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

    private function filterDifferentInsuredWithSameContact(Collection $leads): Collection
    {
        $grouped = $leads->groupBy(function ($lead) {
            return $lead->email ?: $lead->mobile_no;
        });

        foreach ($grouped as $group) {
            if ($group->count() == 24) {
                echo '<pre>'; print_r($group->toArray()); exit;
            }
        }
        exit;
        return $grouped;
    }
}
