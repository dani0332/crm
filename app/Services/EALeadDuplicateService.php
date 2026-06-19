<?php

namespace App\Services;

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
    public function findDuplicate(string $email, int $quoteTypeId): ?Model
    {
        $cutoff = now()->subDays(60);

        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::with('advisor')
                ->where('email', $email)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->whereNull('deleted_at')
                ->latest()
                ->first();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::with('advisor')
                ->where('email', $email)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        if ($quoteTypeId === QuoteTypeId::Travel) {
            return TravelQuote::with('advisor')
                ->where('email', $email)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        if (in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            return BusinessQuote::with('advisor')
                ->where('email', $email)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if ($quoteTypeEnum && checkPersonalQuotes($quoteTypeEnum->value)) {
            return PersonalQuote::with('advisor')
                ->where('email', $email)
                ->where('quote_type_id', $quoteTypeId)
                ->where('quote_status_id', '!=', QuoteStatusEnum::PolicyBooked)
                ->where('created_at', '>=', $cutoff)
                ->latest()
                ->first();
        }

        return null;
    }

    public function isBlockedByRenewalExpiry(string $email, int $quoteTypeId): bool
    {
        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::where('email', $email)
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            return HealthQuote::where('email', $email)
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if ($quoteTypeId === QuoteTypeId::Travel) {
            return TravelQuote::where('email', $email)
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        if (in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
            return BusinessQuote::where('email', $email)
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if ($quoteTypeEnum && checkPersonalQuotes($quoteTypeEnum->value)) {
            return PersonalQuote::where('email', $email)
                ->where('quote_type_id', $quoteTypeId)
                ->where('source', 'Renewal_upload')
                ->where('policy_expiry_date', '>=', now())
                ->exists();
        }

        return false;
    }
}
