<?php

namespace App\Traits\QuoteTraits;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use Carbon\Carbon;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteTag;

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

    public function scopePaymentLinkRequested($q)
    {
        $q->where('quote_status_id', QuoteStatusEnum::PaymentLinkRequestedByCustomer);
    }

    public function scopeHasOneOfPaidStatus($q)
    {
        $q->where(function ($sq) {
            $sq->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])->orWhere->paymentLinkRequested();
        });
    }

    public function scopeRequestedAdvisorOrPaymentAuthorized($q)
    {
        $q->where(function ($sq) {
            $sq->where('sic_advisor_requested', 1)->orWhere->hasOneOfPaidStatus();
        });
    }

    public function isFakeOrDuplicate()
    {
        return in_array($this->quote_status_id, [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
    }

    public function isRequestedAdvisorOrPaymentAuthorized()
    {
        return $this->sic_advisor_requested == 1 || in_array($this->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]) || $this->quote_status_id == QuoteStatusEnum::PaymentLinkRequestedByCustomer;
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

    public function isEligibleForOrganicAssignmentForPlanB(): bool
    {
        return $this->isInsurerPlanB()
            && $this->isSIC(QuoteTypes::CAR)
            && ! $this->sic_advisor_requested
            && $this->quote_status_id === QuoteStatusEnum::PaymentLinkRequestedByCustomer;
    }

    /**
     * Filter AIG leads based on advisor request status - For Car quotes only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterAigLeads($q)
    {
        $table = $q->getModel()->getTable();
        
        return $q->where(function ($query) use ($table) {
            // Either it's not an AIG lead
            $query->whereNotExists(function ($subQuery) use ($table) {
                $subQuery->from('quote_tags')
                    ->whereRaw("quote_tags.quote_uuid = {$table}.uuid")
                    ->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())
                    ->where('quote_tags.quote_type_id', QuoteTypeId::Car);
            })
            // Or it's an AIG lead with sic_advisor_requested = true
            ->orWhere(function ($subQuery) use ($table) {
                $subQuery->whereExists(function ($tagQuery) use ($table) {
                    $tagQuery->from('quote_tags')
                        ->whereRaw("quote_tags.quote_uuid = {$table}.uuid")
                        ->where('quote_tags.name', QuoteSegmentEnum::AIG->tag())
                        ->where('quote_tags.quote_type_id', QuoteTypeId::Car);
                })
                ->where('sic_advisor_requested', true);
            });
        });
    }

    /**
     * Check lead eligibility based on source and flow status
     * 
     * @param \Illuminate\Database\Eloquent\Builder $q
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEligibleForAllocation($q)
    {
        return $q->where(function ($query) {
            $query->where(function ($q) {
                $q->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                  ->sicFlowEnabled()
                  ->requestedAdvisorOrPaymentAuthorized();
            })
            ->orWhere->leadAllocationFailed()
            ->orWhere(function ($q) {
                $q->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                  ->where(function ($inner) {
                      $inner->where(function ($x) {
                          $x->sicFlowDisabled();
                      })->orWhere(function ($x) {
                          $x->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                      });
                  });
            });
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
}
