<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Model;

class EALeadDuplicateService
{
    /**
     * Check for a duplicate lead within a 60-day window.
     *
     * Returns the existing lead (with advisor loaded) if a duplicate is found, null otherwise.
     */
    public function findDuplicate(string $email, string $mobileNo, int $quoteTypeId): ?Model
    {
        $cutoff = now()->subDays(60);

        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::with('advisor')
                ->where(function ($q) use ($email, $mobileNo) {
                    $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
                })
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->whereNull('deleted_at')
                ->latest()
                ->first();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::with('advisor')
                ->where(function ($q) use ($email, $mobileNo) {
                    $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
                })
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        return PersonalQuote::with('advisor')
            ->where(function ($q) use ($email, $mobileNo) {
                $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
            })
            ->where('quote_type_id', $quoteTypeId)
            ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
            ->where('created_at', '>=', $cutoff)
            ->latest()
            ->first();
    }

    /**
     * Check whether a renewal-upload lead is still within its policy expiry period.
     * If so, skip creation.
     */
    public function isBlockedByRenewalExpiry(string $email, string $mobileNo, int $quoteTypeId): bool
    {
        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::where(function ($q) use ($email, $mobileNo) {
                $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
            })
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::where(function ($q) use ($email, $mobileNo) {
                $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
            })
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        return PersonalQuote::where(function ($q) use ($email, $mobileNo) {
            $q->where('email', $email)->orWhere('mobile_no', $mobileNo);
        })
            ->where('quote_type_id', $quoteTypeId)
            ->where('source', 'Renewal_upload')
            ->where('policy_expiry_date', '>=', now())
            ->exists();
    }
}
