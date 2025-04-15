<?php

namespace App\Traits;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasQuoteStatusLogsTrait
{
    /**
     * Get all quote status logs for this model
     *
     * @return MorphMany
     */
    public function quoteStatusLogs(): HasMany
    {
        return $this->hasMany(QuoteStatusLog::class, 'quote_request_id');
    }

    /**
     * Check if quote has both payment link sent and initiated status in its history
     */
    public function hasPaymentLinkHistory(): bool
    {
        // Check for PaymentLinkSentToCustomer status
        $hasPaymentLinkSent = $this->quoteStatusLogs()
            ->where(function ($query) {
                $query->where('previous_quote_status_id', QuoteStatusEnum::PaymentLinkSentToCustomer)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PaymentLinkSentToCustomer);
            })
            ->exists();

        // Check for PaymentInitiated status
        $hasPaymentInitiated = $this->quoteStatusLogs()
            ->where(function ($query) {
                $query->where('previous_quote_status_id', QuoteStatusEnum::PaymentInitiated)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PaymentInitiated);
            })
            ->exists();

        // Return true only if both statuses exist in history
        return $hasPaymentLinkSent && $hasPaymentInitiated;
    }

    /**
     * Check if quote can be updated to transaction approved status
     * Only allowed if quote has both payment link sent and initiated status in history
     */
    public function canUpdateToTransactionApproved(): bool
    {
        return $this->hasPaymentLinkHistory();
    }
}
