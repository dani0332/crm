<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Models\KycLog;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class QuoteStatusService
{
    use GenericQueriesAllLobs;
    public function updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType, $request = [])
    {
        $checkAMlService = new CheckAmlService();
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteStatus = QuoteStatus::where('code', $quoteStatusType)->firstOrFail();

        if (checkPersonalQuotes($quoteType->code) && (! $checkAMlService->isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteRequestId = $checkAMlService->getPersonalQuoteId($quoteTypeId, $quoteRequestId);
            $checkAMlService->updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, true, ['quote_status_id' => $quoteStatus->id]);
        }

        if (! empty($request)) {
            $fetchKycLog = KycLog::where('id', $request['aml_id'])->withTrashed();
            $fetchKycLog->update([
                'decision' => $request['aml_decision'] ?? '',
                'notes' => trim($request['notes']) ?? '',
            ]);

            $kycLog = $fetchKycLog->first();
            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            $quoteStatusID = (AMLService::checkAMLStatusFailed($kycLog->quote_type_id, $kycLog->quote_request_id)) ? QuoteStatusEnum::AMLScreeningFailed : $quoteStatus->id;
            $updateQuote->quote_status_id = $quoteStatusID;

            $previousStatusId = $updateQuote->quote_status_id;
            $currentStatusId = $quoteStatusID;
        } else {
            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            $updateQuote->quote_status_id = $quoteStatus->id;

            $previousStatusId = $updateQuote->quote_status_id;
            $currentStatusId = $quoteStatus->id;
        }

        QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'current_quote_status_id' => $currentStatusId,
            'previous_quote_status_id' => $previousStatusId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if ($updateQuote->save()) {
            $clientFullName = $updateQuote->first_name.' '.$updateQuote->last_name;

            return [
                'quote_status_text' => $quoteStatus->text,
                'quote_ref_id' => $updateQuote->code,
                'quote_type_text' => $quoteType->text,
                'pa_id' => $updateQuote->pa_id,
                'client_name' => $clientFullName,
            ];
        } else {
            return 'false';
        }
    }
}
