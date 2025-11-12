<?php

namespace App\Traits;

use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\QuoteStatus;
use App\Models\User;

trait PersonalQuoteLobs
{
    public function getPersonalQuoteAdvisors($quoteType)
    {
        $roleSuffix = strtoupper($quoteType);

        $roles = $quoteType == QuoteTypes::PET->value
            ? ["{$roleSuffix}_ADVISOR", "{$roleSuffix}_RENEWAL_ADVISOR", "{$roleSuffix}_NEW_BUSINESS_ADVISOR"]
            : ["{$roleSuffix}_ADVISOR"];

        return User::with(['roles' => fn ($q) => $q->whereIn('name', $roles)])
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            })->get();
    }

    public function getPersonalQuoteStatuses($quoteTypeId)
    {
        return QuoteStatus::select('quote_status.id as id', 'quote_status.text as text', 'quote_status.code as code')
            ->where(['quote_status.is_active' => true, 'quote_status_map.quote_type_id' => $quoteTypeId])
            ->leftjoin('quote_status_map', 'quote_status.id', 'quote_status_map.quote_status_id')
            ->orderBy('quote_status_map.sort_order', 'asc');
    }
}
