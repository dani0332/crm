<?php

namespace App\Repositories;

use App\Models\SageApiLog;

class SageApiLogRepository extends BaseRepository
{
    public function model()
    {
        return SageApiLog::class;
    }

    public function fetchGetInvoiceResponse($request)
    {
        return $this->where([
            'section_type' => $request['quoteTypeObject'],
            'section_id' => $request['quoteTypeId'],
            'sage_request_type' => $request['invoiceType'],
        ])->first()?->response;
    }
}
