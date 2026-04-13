<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Models\DttRevival;
use App\Models\QuoteBatches;

class DTTRevivalService
{
    public function create($revivalQuoteId, $revivalQuoteUUID, $previousQuoteId)
    {
        $quoteBatch = QuoteBatches::latest()->first();

        DttRevival::create([
            'quote_type_id' => QuoteTypes::LIFE->id(),
            'quote_id' => $revivalQuoteId,
            'uuid' => $revivalQuoteUUID,
            'revival_quote_batch_id' => $quoteBatch->id,
            'previous_quote_id' => $previousQuoteId,
            'email_sent' => true,
        ]);
    }
}
