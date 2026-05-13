<?php

namespace App\Services;

use App\Models\DttRevival;
use App\Models\QuoteBatches;

class DTTRevivalService
{
    public function create($revivalQuoteId, $revivalQuoteUUID, $previousQuoteId, $quoteTypeId)
    {
        $quoteBatch = QuoteBatches::latest()->first();

        DttRevival::create([
            'quote_type_id' => $quoteTypeId,
            'quote_id' => $revivalQuoteId,
            'uuid' => $revivalQuoteUUID,
            'revival_quote_batch_id' => $quoteBatch?->id ?? null,
            'previous_quote_id' => $previousQuoteId,
            'email_sent' => true,
        ]);
    }
}
