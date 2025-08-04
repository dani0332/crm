<?php

namespace App\Repositories;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use Carbon\Carbon;

class QuoteStatusLogRepository extends BaseRepository
{
    public function model()
    {
        return QuoteStatusLog::class;
    }

    public function fetchCreate($quoteTypeId, $quote, $oldQuoteStatus)
    {
        $this->create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if quote has transaction approved status in its history
     */
    public function fetchHasTransactionApprovedStatus($quoteTypeId, $quoteId)
    {
        return $this->where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteId)
            ->where(function ($query) {
                $query->where('current_quote_status_id', QuoteStatusEnum::TransactionApproved)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::TransactionApproved);
            })->exists();
    }
}
