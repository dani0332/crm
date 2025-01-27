<?php

namespace App\Traits\QuoteTraits;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use Carbon\Carbon;

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

    public function scopeHasOneOfPaidStatus($q)
    {
        $q->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
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
        return $this->sic_advisor_requested == 1 || in_array($this->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
    }

    public function isRenewalUpload()
    {
        return $this->source == LeadSourceEnum::RENEWAL_UPLOAD;
    }

    public function isRevivalRepliedOrPaid()
    {
        return in_array($this->source, [LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID]);
    }
}
