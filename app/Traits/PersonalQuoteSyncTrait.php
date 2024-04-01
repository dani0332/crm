<?php

namespace App\Traits;

use App\Models\PersonalQuoteDetail;
use App\Repositories\PersonalQuoteRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait PersonalQuoteSyncTrait
{
    public function syncQuote($quote, $updatedFields)
    {
        // update personal quote
        $personalQuote = PersonalQuoteRepository::where('uuid', $quote->uuid)->first();
        if (! $personalQuote) {
            Log::warning("Quote not found in personal quotes table, uuid: {$quote->uuid}");

            return;
        }

        $this->syncTable($personalQuote, $updatedFields, 'personal_quotes');
        $personalQuote->save();
    }

    public function syncQuoteDetail($quote, $updatedFields)
    {
        // get personal quote
        $personalQuote = PersonalQuoteRepository::where('uuid', $quote->uuid)->first();
        if (! $personalQuote) {
            Log::warning("Quote not found in personal quotes table, uuid: {$quote->uuid}");

            return;
        }

        // update personal quote details
        $personalQuoteDetail = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();
        if (! $personalQuoteDetail) {
            Log::warning("Quote details not found in personal quote details table, personal_quote_id: {$personalQuote->id}");

            return;
        }

        $this->syncTable($personalQuoteDetail, $updatedFields, 'personal_quote_details');
        $personalQuoteDetail->save();
    }

    public function syncTable($quote, $updatedFields, $quoteTable)
    {
        foreach ($updatedFields as $column => $value) {
            if ($column === 'id' || $column === 'currently_insured_with') {
                continue;
            }
            if (Schema::hasColumn($quoteTable, $column)) {
                $columnType = DB::getSchemaBuilder()->getColumnType($quoteTable, $column);
                $value = $this->formatColumnValue($columnType, $value);
                if ($value !== null || $value !== '' || $value !== 'NULL') {
                    $quote->$column = $value;
                }
            }
        }
    }

    private function formatColumnValue($columnType, $value)
    {
        if (in_array($columnType, ['date', 'datetime'])) {
            return Carbon::parse($value)->toDateTimeString();
        }

        return $value;
    }
}
