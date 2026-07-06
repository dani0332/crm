<?php

namespace App\Services;

use App\Models\GroupMedicalQuoteCategory;

class GroupMedicalQuoteCategoryService
{
    public function getCategoryCountByQuoteId(int $quoteId): int
    {
        return GroupMedicalQuoteCategory::where('business_quote_request_id', $quoteId)->count();
    }
}
