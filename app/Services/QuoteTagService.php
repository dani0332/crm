<?php

namespace App\Services;

use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BrokerCommission;
use App\Models\QuoteTag;

class QuoteTagService
{
    /**
    * Check if the TAP capture process has started for a given quote.
    *
    * @param object|null $quote
    * @param int $quoteTypeId
    * @return bool
    */
    public function isTapCaptureProcessStart($quote, $quoteTypeId)
    {
        return QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START,
            'value' => 1,
        ])
        ->select('id')
        ->limit(1)
        ->exists();
    }
}
