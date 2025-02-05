<?php

namespace App\Services;

use App\Enums\QuoteTagEnums;
use App\Models\QuoteTag;

class QuoteTagService
{
    /**
     * Check if the TAP capture process has started for a given quote.
     *
     * @param  object|null  $quote
     * @param  int  $quoteTypeId
     * @return bool
     */
    public function isTapCaptureProcessStart($quote, $quoteTypeId, $sendUpdateLog = null)
    {
        $whereClause = [
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START,
            'value' => 1,
        ];

        if ($sendUpdateLog) {
            $whereClause['name'] = QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_SU_START.'-'.$sendUpdateLog->id;
            $whereClause['send_update_log_id'] = $sendUpdateLog->id;
        }
        return QuoteTag::where($whereClause)
            ->select('id')
            ->limit(1)
            ->exists();
    }
}
