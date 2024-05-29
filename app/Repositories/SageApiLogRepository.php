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
        $sendRequestTypes = [
            'createARInvoicePremAndComm' => SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
            'createAPInvoicePrem' => SageEnum::SRT_CREATE_AP_PREM_INV,
            'createARInvoiceDis' => SageEnum::SRT_CREATE_AR_DISC_INV,
        ];
        $getReverseInvSendUpdate = SendUpdateLog::where('insurer_tax_invoice_number', $request['reversal_invoice_number'])->first();
        $getSageApiLogsForReversal = $this->where([
            'section_type' => $request['quoteTypeObject'],
            'section_id' => $getReverseInvSendUpdate->id,
            'sage_request_type' => $sendRequestTypes[$request['method']] ?? null,
        ])->first();
        $batchNumber = json_decode($getSageApiLogsForReversal->response)->BatchNumber ?? null;

        $invoiceResponse = $this->where([
            'section_type' => $request['quoteTypeObject'],
            'section_id' => $request['quote_id'],
            'sage_request_type' => $request['invoiceType'],
        ])->where('sage_end_point', 'LIKE', '%'.$batchNumber.'%')->first()?->response;

        return $invoiceResponse;
    }
}
