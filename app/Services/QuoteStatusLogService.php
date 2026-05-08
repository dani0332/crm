<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class QuoteStatusLogService extends BaseService
{
    // todo: will move this to event listener
    public function createQuoteStatusLog($quoteTypeId, $quote, $oldQuoteStatus)
    {
        QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
        ]);
    }

    /**
     * Check if quote has transaction approved status in its history
     */
    public function hasTransactionApprovedStatus($quoteTypeId, $quoteId)
    {
        return QuoteStatusLog::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteId)
            ->where(function ($query) {
                $query->where('current_quote_status_id', QuoteStatusEnum::TransactionApproved)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::TransactionApproved);
            })->exists();
    }

    public function getQuoteStatusLogs(?int $quoteTypeId = null, ?int $quoteId = null, ?int $sendUpdateId = null): EloquentCollection
    {
        $query = QuoteStatusLog::query()
            ->with(['currentQuoteStatus', 'createdBy', 'previousQuoteStatus'])
            ->orderBy('created_at', 'DESC');

        if ($sendUpdateId !== null) {
            $query->where('send_update_log_id', $sendUpdateId);
        } else {
            if ($quoteTypeId === null || $quoteId === null) {
                return new EloquentCollection;
            }

            $query
                ->where('quote_type_id', $quoteTypeId)
                ->where('quote_request_id', $quoteId);
        }

        return $query->get();
    }

}
