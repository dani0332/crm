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
    public function isCapturePaymentStarted($quoteUUid, $quoteTypeId, $sendUpdateLogId = null)
    {
        $whereClause = [
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quoteUUid,
            'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START,
            'value' => 1,
        ];

        if ($sendUpdateLogId) {
            $whereClause['name'] = QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_SU_START.'-'.$sendUpdateLogId;
            $whereClause['send_update_log_id'] = $sendUpdateLogId;
        }

        return QuoteTag::where($whereClause)
            ->select('id')
            ->limit(1)
            ->exists();
    }
}
