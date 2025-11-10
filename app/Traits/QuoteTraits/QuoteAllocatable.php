<?php

namespace App\Traits\QuoteTraits;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteTag;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait QuoteAllocatable
{
    public function startAllocation()
    {
        if ($this->lead_allocation_started_at) {
            return; // Already Started
        }

        self::withoutEvents(function () {
            $this->update([
                'lead_allocation_started_at' => now(),
            ]);
        });
    }

    public function isAllocationInProgress(): bool
    {
        // We will consider the lead to be in progress if attempted within 10 minutes of the last attempt
        return ! empty($this->lead_allocation_started_at) && Carbon::parse($this->lead_allocation_started_at)->greaterThanOrEqualTo(now()->subMinutes(10));
    }

    public function endAllocation()
    {
        if (! $this->lead_allocation_started_at) {
            return; // Already Ended
        }

        self::withoutEvents(function () {
            $this->update([
                'lead_allocation_started_at' => null,
            ]);
        });
    }

    public function markLeadAllocationFailed()
    {
        if ($this->advisor_id) {
            // if advisor is already assigned then we don't need to mark it as failed

            return;
        }

        if ($this->lead_allocation_failed_at) {
            self::withoutEvents(function () {
                $this->update([
                    'lead_allocation_started_at' => null,
                ]);
            });

            return; // Already marked as failed
        }

        self::withoutEvents(function () {
            $this->update([
                'lead_allocation_failed_at' => now(),
                'lead_allocation_started_at' => null,
            ]);
        });
    }

    public function markLeadAllocationPassed()
    {
        if (! $this->lead_allocation_failed_at || ! $this->advisor_id) {
            self::withoutEvents(function () {
                $this->update([
                    'lead_allocation_started_at' => null,
                ]);
            });

            return; // Already marked as passed or advisor not assigned
        }

        self::withoutEvents(function () {
            $this->update([
                'lead_allocation_failed_at' => null,
                'lead_allocation_started_at' => null,
            ]);
        });
    }

    public function scopeLeadAllocationFailed($q)
    {
        $q->whereNotNull('lead_allocation_failed_at');
    }

    public function scopeSicFlowEnabled($q, bool $enabled = true)
    {
        $q->where('sic_flow_enabled', $enabled);
    }

    public function scopeSicFlowDisabled($q)
    {
        $q->where(function ($query) {
            $query->sicFlowEnabled(false)->orWhereNull('sic_flow_enabled');
        });
    }

    public function isSICFlowEnabled()
    {
        return $this->sic_flow_enabled;
    }

    public function isSICFlowDisabled()
    {
        return ! $this->isSICFlowEnabled();
    }

    public function isFakeOrDuplicate()
    {
        return in_array($this->quote_status_id, [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
    }

    public function isRenewalUpload()
    {
        return $this->source == LeadSourceEnum::RENEWAL_UPLOAD;
    }

    public function isRevivalRepliedOrPaid()
    {
        return in_array($this->source, [LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID]);
    }

    public function isInsurerPlanB()
    {
        return $this->insuranceProvider?->payment_gateway_id === PaymentGatewayEnum::PAYMENT_GATEWAY_PAYMENT_LINK;
    }

    public function isEligibleForOrganicAssignmentForPlanB(QuoteTypes $quoteType): bool
    {
        return $this->isInsurerPlanB()
            && $this->isSIC($quoteType)
            && ! $this->sic_advisor_requested
            && $this->quote_status_id === QuoteStatusEnum::PaymentLinkRequestedByCustomer;
    }

    /**
     * Filters leads that are eligible for allocation.
     * Includes both flow-based and AIG-specific filtering logic.
     * Its being used in QuoteAllocation.php and for generic purpose for LOBs
     * So kindly do not change the logic without discussing with team
     */
    public function scopeEligibleForAllocation(Builder $query, QuoteTypes $quoteType): Builder
    {

        return $query->where(function ($mainQuery) use ($quoteType) {
            $mainQuery
                // AIG leads with advisor requested or payment authorized
                ->where(function ($aigQuery) use ($quoteType) {
                    $aigQuery->isAIG($quoteType)
                        ->advisorRequestedOrPaymentAuthorizedOrDeclined();
                })

                // OR Other lead types
                ->orWhere(function ($otherLeads) use ($quoteType) {
                    $otherLeads->isNotAIG($quoteType);
                    $otherLeads->where(function ($sq) {
                        $sq->where(function ($q) {
                            $q->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                                ->sicFlowEnabled()
                                ->advisorRequestedOrPaymentAuthorizedOrDeclined();
                        })
                        // Non-renewal leads with SIC logic
                            ->orWhere(function ($q) {
                                $q->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                                    ->where(function ($inner) {
                                        $inner
                                            ->where(fn ($x) => $x->sicFlowDisabled())
                                            ->orWhere(fn ($x) => $x->sicFlowEnabled()->advisorRequestedOrPaymentAuthorizedOrDeclined());
                                    });
                            });
                    });
                });
        })->orWhere->leadAllocationFailed();
    }

    protected function aigSubQuery($subQuery, $table, QuoteTypes $quoteType)
    {
        $subQuery->select(DB::raw(1))->from('quote_tags')
            ->whereColumn('quote_tags.quote_uuid', "{$table}.uuid")
            ->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())
            ->where('quote_tags.quote_type_id', $quoteType->id());
    }

    protected function scopeIsAIG($query, QuoteTypes $quoteType): void
    {
        $table = $query->getModel()->getTable();
        $query->whereExists(function ($subQuery) use ($table, $quoteType) {
            $this->aigSubQuery($subQuery, $table, $quoteType);
        });
    }

    protected function scopeIsNotAIG($query, QuoteTypes $quoteType): void
    {
        $table = $query->getModel()->getTable();
        $query->whereNotExists(function ($subQuery) use ($table, $quoteType) {
            $this->aigSubQuery($subQuery, $table, $quoteType);
        });
    }

    public function isAdvisorRequested()
    {
        return $this->sic_advisor_requested == 1;
    }

    public function isAIG(QuoteTypes $quoteType): bool
    {
        return QuoteTag::where('quote_uuid', $this->uuid)->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())->where('quote_tags.quote_type_id', $quoteType->id())->exists();
    }

    public function isLeadFromInstantAlfred(): bool
    {
        return $this->lead_assignment_trigger == LeadAssignmentTriggerEnum::INSTANT_ALFRED;
    }

    public function isFIC(QuoteTypes $quoteType): bool
    {
        return QuoteTag::where('quote_uuid', $this->uuid)
            ->where('quote_tags.name', QuoteSegmentEnum::FIC->tag())
            ->where('quote_tags.quote_type_id', $quoteType->id())->exists();
    }

    public function isAIAdviserRequired(): bool
    {
        return (bool) $this->ai_advisor_required;
    }

    public function assignToAIAdvisor()
    {
        if ($this->isAIAdvisorAssigned()) {
            return;
        }

        $aiAdvisor = User::getAiAdvisor();

        if (! $aiAdvisor) {
            LoggerService::warning('AI Advisor Not Found');

            return;
        }

        $this->update([
            'ai_advisor_assigned_at' => now(),
            'advisor_id' => $aiAdvisor->id,
        ]);
    }

    public function isAIAdvisorAssigned(): bool
    {
        return ! empty($this->advisor) && $this->advisor->isAi() && ! empty($this->ai_advisor_assigned_at);
    }

    public function isAIAdvisorEverAssigned(): bool
    {
        return ! empty($this->ai_advisor_assigned_at);
    }

    public function isReAssignment()
    {
        return in_array($this->assignment_type, [AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD]);
    }

    // similar to eligibleForAllocation but checks sic_advisor_requested via cyberQuoteRequest relation.
    public function scopeEligibleForAllocationCyber(Builder $query): Builder
    {
        return $query->where(function ($mainQuery) {
            $mainQuery
                // AIG Cyber leads with advisor requested or payment authorized/declined
                ->where(function ($aigQuery) {
                    $aigQuery->isAIG(QuoteTypes::CYBER)
                        ->advisorRequestedOrPaymentAuthorizedOrDeclinedCyber();
                })
                // OR Non-AIG Cyber leads
                ->orWhere(function ($otherLeads) {
                    $otherLeads->isNotAIG(QuoteTypes::CYBER);
                    $otherLeads->where(function ($sq) {
                        $sq
                            ->where(fn ($x) => $x->sicFlowDisabled())
                            // SIC flow enabled with advisor requested or payment
                            ->orWhere(fn ($x) => $x->sicFlowEnabled()->advisorRequestedOrPaymentAuthorizedOrDeclinedCyber());
                    });
                });
        })->orWhere->leadAllocationFailed();
    }
}
