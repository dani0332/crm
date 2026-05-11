<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\StatusChangeActionEnum;
use App\Models\QuoteStatusLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

class QuoteStatusLogService extends BaseService
{
    public function createQuoteStatusLog(
        int $quoteTypeId,
        Model $quote,
        ?int $oldQuoteStatus = null,
    ): QuoteStatusLog {
        $log = QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->getKey(),
            'current_quote_status_id' => $quote->quote_status_id,
            'previous_quote_status_id' => $oldQuoteStatus,
            'status_change_source' => LeadSourceEnum::IMCRM,
            'status_change_action' => $this->normalizeStatusChangeAction(Context::get('status_change_action')),
            'send_update_log_id' => Context::get('send_update_log_id'),
            'notes' => $this->buildStatusChangeNotes(),
            'created_by' => Auth::id(),
        ]);

        $this->forgetStatusChangeContext();

        return $log;
    }

    /**
     * One-shot semantics: status_change_action and send_update_log_id are consumed
     * by the log entry and must not leak into subsequent status changes within the
     * same request lifecycle.
     */
    protected function forgetStatusChangeContext(): void
    {
        Context::forget('status_change_action');
        Context::forget('send_update_log_id');
    }

    /**
     * @return string|null Stored enum value, or null when no action was set in context.
     */
    protected function normalizeStatusChangeAction(mixed $action): ?string
    {
        if ($action instanceof StatusChangeActionEnum) {
            return $action->value;
        }

        if (is_string($action) && $action !== '') {
            return StatusChangeActionEnum::tryFrom($action)?->value ?? $action;
        }

        return null;
    }

    /**
     * Build a JSON payload of request metadata persisted in the `notes` text column.
     */
    protected function buildStatusChangeNotes(): string
    {
        $request = request();

        return json_encode([
            'method' => $request?->method(),
            'path' => $request?->path(),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ], JSON_UNESCAPED_SLASHES);
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
