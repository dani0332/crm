<?php

namespace App\Repositories;

use App\Enums\SageEnum;
use App\Models\SageApiLog;
use App\Models\SendUpdateLog;

class SageApiLogRepository extends BaseRepository
{
    public function model()
    {
        return SageApiLog::class;
    }

    public function fetchGetInvoiceResponse($request)
    {
        $batchNumber = json_decode($request['reverseInvoiceDetails']['response'])->BatchNumber ?? null;
        $invoiceResponse = $this->where([
            'section_type' => $request['quoteTypeObject'],
            'section_id' => $request['quoteTypeId'],
            'sage_request_type' => $request['invoiceType'],
        ])->where('sage_end_point', 'LIKE', '%'.$batchNumber.'%')->first()?->response;

        return $invoiceResponse;
    }
}
