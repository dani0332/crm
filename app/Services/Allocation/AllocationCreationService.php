<?php

namespace App\Services\Allocation;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use Illuminate\Support\Collection;

class AllocationCreationService
{
    public function executeLifeRevivalAllocation(): Collection
    {
        // Step 1: Get all LIFE quotes eligible for revival
        $leadsToRevive = PersonalQuote::with('lifeQuote')->whereHas('lifeQuote')
            ->where('quote_type_id', QuoteTypeId::Life)->whereNot('source', LeadSourceEnum::REVIVAL)
            ->whereDate('created_at', '<=', now()->subDays(90))
            ->get();

        // Step 2: Filter duplicate insured
        $filteredLeads = $this->filterDuplicateInsured($leadsToRevive);

        // Step 3: Different insured with same contact
        $differentInsuredWithSameContact = $this->filterDifferentInsuredWithSameContact($filteredLeads);

        // Step 4: Just exclude all leads with policybooked status
        $filteredLeads = $differentInsuredWithSameContact->filter(function ($lead) {
            return $lead->quote_status_id != QuoteStatusEnum::PolicyBooked;
        });

        return $filteredLeads;
    }

    private function filterDuplicateInsured(Collection $leads): Collection
    {
        // Step 1: Group quotes by dob and gender
        $grouped = $leads->groupBy(function ($lead) {
            return $lead->dob.'_'.$lead->gender;
        });

        // Step 2: Exclude group if any quote in the group is PolicyBooked
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
        /*$filteredLeads = $filteredLeads->filter(function ($item) {
            return $item->quote_status_id != QuoteStatusEnum::PolicyBooked;
        });*/

        return $filteredLeads;
    }

    private function filterDifferentInsuredWithSameContact(Collection $leads): Collection
    {
        $filteredLeads = collect();
        // Group leads with wither email or monile_no same
        $grouped = $leads->groupBy(function ($lead) {
            return $lead->email ?: $lead->mobile_no;
        });

        foreach ($grouped as $group) {
            // Case 1: If single lead in group, just add
            if ($group->count() == 1) {
                $filteredLeads = $filteredLeads->merge($group);

                continue;
            }

            // Case 2: Multi-lead group
            // Check if any lead is PolicyBooked
            $groupWithPolicyBooked = $group->contains(function ($lead) {
                return $lead->quote_status_id == QuoteStatusEnum::PolicyBooked;
            });

            if ($groupWithPolicyBooked) {
                // Merge only the leads with NOT PolicyBooked status
                $nonPolicyBookedLeads = $group->filter(function ($lead) {
                    return $lead->quote_status_id != QuoteStatusEnum::PolicyBooked;
                });

                $filteredLeads = $filteredLeads->merge($nonPolicyBookedLeads);

                continue;
            }

            // Case 3: Multi-lead group with No PolicyBooked
            $filteredLeads = $filteredLeads->merge($group);
        }

        return $filteredLeads;
    }
}
