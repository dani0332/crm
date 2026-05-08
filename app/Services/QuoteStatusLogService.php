<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuoteStatusLogService extends BaseService
{
    /**
     * @param  array{
     *     status_change_source?: string|null,
     *     notes?: string|null,
     *     send_update_log_id?: int|null,
     *     created_by?: int|null,
     *     personal_quote_id?: int|null
     * }  $context
     */
    public function createQuoteStatusLogWithContext(
        int $quoteTypeId,
        Model $quote,
        int $oldQuoteStatus,
        ?string $statusChangeAction = null,
        array $context = []
    ): QuoteStatusLog {
        $notes = $this->resolveStatusChangeNotes($context['notes'] ?? null);

        return QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->getKey(),
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
            'status_change_source' => LeadSourceEnum::IMCRM,
            'status_change_action' => $statusChangeAction,
            'notes' => $notes,
            'send_update_log_id' => $context['send_update_log_id'] ?? null,
            'created_by' => $context['created_by'] ?? Auth::id(),
            'personal_quote_id' => $context['personal_quote_id'] ?? null,
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

    private function resolveStatusChangeNotes(?string $notes): ?string
    {
        if ($notes !== null && $notes !== '') {
            return $notes;
        }

        if (app()->runningInConsole()) {
            return null;
        }

        return request()->method().' '.request()->path();
    }

}
