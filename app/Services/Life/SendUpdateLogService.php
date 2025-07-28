<?php

namespace App\Services\Life;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\BaseService;
use App\Traits\GenericQueriesAllLobs;

class SendUpdateLogService extends BaseService
{
    use GenericQueriesAllLobs;

    public function linkedQuoteDetails($quote)
    {
        $childRecords = PersonalQuote::where('parent_duplicate_quote_id', $quote->code)->get();

        $quoteDetails = [
            'quote_type_id' => QuoteTypeId::Life,
            'parent_lead_ref_id' => '',
            'uuid' => '',
            'childLeadsCount' => $childRecords->count(),
            'childLeads' => '',
            'childLeadsUuid' => '',
        ];

        if (! empty($quote->parent_duplicate_quote_id)) {
            $quoteDetails['parent_lead_ref_id'] = $quote->parent_duplicate_quote_id;
            $quoteDetails['uuid'] = explode('-', $quote->parent_duplicate_quote_id)[1];
        }

        if ($childRecords->count() <= 1) {
            $quoteDetails['childLeads'] = $childRecords->value('code');
            $quoteDetails['childLeadsUuid'] = $childRecords->value('uuid');
        }

        return $quoteDetails;
    }

    public function byQuoteUuid($uuid)
    {
        return SendUpdateLog::with(['category', 'option'])
            ->where('quote_uuid', $uuid)
            ->get();
    }
}
