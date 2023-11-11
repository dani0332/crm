<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\KycLog;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;

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

        if (!empty($request)) {
            $fetchKycLog = KycLog::where('id', $request['aml_id'])->withTrashed();
            $fetchKycLog->update([
                'decision' => $request['aml_decision'] ?? '',
                'notes' => trim($request['notes']) ?? '',
            ]);

            $kycLog = $fetchKycLog->first();
            $kycLogs = KycLog::where(['quote_request_id' => $kycLog->quote_request_id, 'quote_type_id' => $kycLog->quote_type_id])
                ->where( function($aml) use ($quoteRequestId, $quoteTypeId) {
                    $aml->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                    $aml->orWhereNull('decision');
                })->withTrashed()->get()->pluck('decision')->toArray();

            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            $quoteStatusID = (in_array(AMLDecisionStatusEnum::TRUE_MATCH_REJECT_RISK, $kycLogs)) ? QuoteStatusEnum::AMLScreeningFailed : $quoteStatus->id;
            $updateQuote->quote_status_id = $quoteStatusID;
        } else {
            $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
            $updateQuote->quote_status_id = $quoteStatus->id;
        }

        if ($updateQuote->save()) {
            $clientFullName = $updateQuote->first_name.' '.$updateQuote->last_name;

            return [$quoteStatus->text, $updateQuote->code, $quoteType->text, $updateQuote->pa_id, $clientFullName];
        } else {
            return 'false';
        }
    }
}
