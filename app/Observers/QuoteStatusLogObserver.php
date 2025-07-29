<?php

namespace App\Observers;

use App\Enums\QuoteTypes;
use App\Models\QuoteStatusLog;

class QuoteStatusLogObserver
{
    public function creating(QuoteStatusLog $model)
    {
        $model->personal_quote_id = $this->getPersonalQuoteId($model);
    }

    private function getPersonalQuoteId(QuoteStatusLog $quoteStatusLog)
    {
        $quoteType = QuoteTypes::getName($quoteStatusLog->quote_type_id);

        $isMigratedToPQ = checkPersonalQuotes($quoteType->value);

        if ($isMigratedToPQ) {
            return $quoteStatusLog->quote_request_id;
        }

        $lead = $quoteType->model()->where('id', $quoteStatusLog->quote_request_id)->first();

        if ($lead?->personal_quote_id) {
            return $lead->personal_quote_id;
        }

        $table = $quoteType->model()->getTable();
        $lead = $quoteType->model()->select('personal_quotes.id as personal_quote_id')
            ->where("{$table}.id", $quoteStatusLog->quote_request_id)
            ->join('personal_quotes', 'personal_quotes.uuid', "{$table}.uuid")
            ->first();

        return $lead?->personal_quote_id ?? null;
    }
}
