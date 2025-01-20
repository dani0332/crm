<?php

namespace App\Services\Life;

use App\Enums\QuoteTypeId;
use App\Models\SendUpdateLog;
use App\Services\BaseService;
use App\Traits\GenericQueriesAllLobs;

class SendUpdateLogService extends BaseService
{
    use GenericQueriesAllLobs;

    public function linkedQuoteDetails($quoteTypeCode, $quote)
    {
        $quoteTypeId = QuoteTypeId::getValue($quoteTypeCode);
        $quoteModel = $this->getModelObject($quoteTypeCode);
        $childRecords = $quoteModel::where('parent_duplicate_quote_id', $quote->code)->get();

        $_return = [
            'quote_type_id' => $quoteTypeId,
            'parent_lead_ref_id' => '',
            'uuid' => '',
            'childLeadsCount' => $childRecords->count(),
            'childLeads' => '',
            'childLeadsUuid' => '',
        ];

        if (! empty($quote->parent_duplicate_quote_id)) {
            $_return['parent_lead_ref_id'] = $quote->parent_duplicate_quote_id;
            $_return['uuid'] = explode('-', $quote->parent_duplicate_quote_id)[1];
        }

        if ($childRecords->count() <= 1) {
            $_return['childLeads'] = $childRecords->value('code');
            $_return['childLeadsUuid'] = $childRecords->value('uuid');
        }

        return $_return;
    }

    public function byQuoteUuid($uuid)
    {
        return SendUpdateLog::with(['category', 'option'])
            ->where('quote_uuid', $uuid)
            ->get();
    }
}
