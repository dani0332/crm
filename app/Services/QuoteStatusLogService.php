<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use App\Support\StatusChangeRequestNotes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuoteStatusLogService extends BaseService
{
    public function createQuoteStatusLog(
        int $quoteTypeId,
        Model $quote,
        ?int $oldQuoteStatus = null,
    ): QuoteStatusLog {
        return QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->getKey(),
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
            'status_change_source' => LeadSourceEnum::IMCRM,
            'notes' => StatusChangeRequestNotes::toJson(request()),
            'created_by' => Auth::id(),
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

    public function getQuoteStatusLogs(int $quoteTypeId, int $quoteId): EloquentCollection
    {
        $query = QuoteStatusLog::query()
            ->where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteId)
            ->with(['currentQuoteStatus', 'createdBy', 'previousQuoteStatus'])
            ->orderBy('created_at', 'DESC');

        return $query->get();
    }
}
