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
            ->where('is_revived', false)
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
        // Step 1: Group quotes by dob and gender (same insured). Missing dob or gender
        // cannot identify duplicates; grouping those together would merge unrelated leads
        // (e.g. all null DOB + same gender → one bucket) and drop them when any is PolicyBooked.
        $grouped = $leads->groupBy(fn ($lead) => $this->duplicateInsuredGroupKey($lead));

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

        return $filteredLeads;
    }

    /**
     * Key for grouping leads that represent the same insured (DOB + gender).
     */
    private function duplicateInsuredGroupKey(PersonalQuote $lead): string
    {
        if (filled($lead->dob) && filled($lead->gender)) {
            return (string) $lead->dob.'_'.$lead->gender;
        }

        return 'insufficient_identity_'.$lead->id;
    }

    private function filterDifferentInsuredWithSameContact(Collection $leads): Collection
    {
        $filteredLeads = collect();
        // Group leads with wither email or monile_no same
        $grouped = $leads
            ->filter(function ($lead) {
                return ! empty($lead->email) || ! empty($lead->mobile_no);
            })
            ->groupBy(function ($lead) {
                return ! empty($lead->email)
                    ? $lead->email
                    : $lead->mobile_no;
            });

        // Apply filters as per business logic
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
