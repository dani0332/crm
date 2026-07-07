<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use Illuminate\Database\Eloquent\Model;

class EALeadDuplicateService
{
    public function findDuplicate(string $email, string $mobileNo, int $quoteTypeId): ?Model
    {
        $cutoff = now()->subDays(60);

        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::with('advisor')
                ->where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->whereNull('deleted_at')
                ->latest()
                ->first();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::with('advisor')
                ->where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        if ($quoteTypeId === QuoteTypeId::Travel) {
            return TravelQuote::with('advisor')
                ->where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        if (in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            return BusinessQuote::with('advisor')
                ->where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if ($quoteTypeEnum && checkPersonalQuotes($quoteTypeEnum->value)) {
            return PersonalQuote::with('advisor')
                ->where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_type_id', $quoteTypeId)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        return null;
    }

    public function isBlockedByRenewalExpiry(string $email, string $mobileNo, int $quoteTypeId): bool
    {
        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if ($quoteTypeId === QuoteTypeId::Travel) {
            return TravelQuote::where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if (in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            return BusinessQuote::where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if ($quoteTypeEnum && checkPersonalQuotes($quoteTypeEnum->value)) {
            return PersonalQuote::where(fn ($q) => $q->where('email', $email)->orWhere('mobile_no', $mobileNo))
                ->where('quote_type_id', $quoteTypeId)
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        return false;
    }
}
