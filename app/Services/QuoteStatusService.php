<?php

namespace App\Services;

use App\Enums\AMLStatusEnum;
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

    public function updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType, $request = [], $notes = null)
    {
        $AMLService = new AMLService;
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteStatus = QuoteStatus::where('code', $quoteStatusType)->firstOrFail();

        if (checkPersonalQuotes($quoteType->code) && (! $AMLService->isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteRequestId = $AMLService->getPersonalQuoteId($quoteTypeId, $quoteRequestId);
            $AMLService->updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, true, ['quote_status_id' => $quoteStatus->id]);
        }

        if (! empty($request)) {
            $fetchKycLog = KycLog::where('id', $request['aml_id'])->withTrashed();
            $fetchKycLog->update([
                'decision' => $request['aml_decision'] ?? '',
                'notes' => trim($request['notes']) ?? '',
                'in_adverse_media' => isset($request['in_adverse_media']) ? trim($request['in_adverse_media']) : '',
                'is_owner_pep' => isset($request['is_owner_pep']) ? trim($request['is_owner_pep']) : '',
                'is_controlling_pep' => isset($request['is_controlling_pep']) ? trim($request['is_controlling_pep']) : '',
            ]);

            $kycLog = $fetchKycLog->first();
            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            $quoteStatusID = (AMLService::checkAMLStatusFailed($kycLog->quote_type_id, $kycLog->quote_request_id)) ? QuoteStatusEnum::AMLScreeningFailed : $quoteStatus->id;
            //            $updateQuote->quote_status_id = $quoteStatusID;
            $updateQuote->aml_status_id = AMLStatusEnum::AMLScreeningCleared;

            $previousStatusId = $updateQuote->quote_status_id;
            $currentStatusId = $quoteStatusID;
        } else {
            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            //            $updateQuote->quote_status_id = $quoteStatus->id;
            $updateQuote->aml_status_id = AMLStatusEnum::AMLScreeningFailed;

            $previousStatusId = $updateQuote->quote_status_id;
            $currentStatusId = $quoteStatus->id;
        }

        QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'current_quote_status_id' => $currentStatusId,
            'previous_quote_status_id' => $previousStatusId,
            'notes' => $notes ?? null,
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
