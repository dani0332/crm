<?php

namespace App\Services\Life;

use App\Models\Activities;
use App\Services\BaseService;

class ActivityService extends BaseService
{
    public function getQuoteActivities($quoteTypeId, $quoteRequestId)
    {
        return Activities::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();
    }
}
