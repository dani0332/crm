<?php

namespace App\Services;

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

        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);
        $updateQuote->quote_status_id = $quoteStatus->id;

        if ($updateQuote->save()) {

            if (!empty($request)) {
                KycLog::where('id', $request['aml_id'])->withTrashed()->update([
                    'decision' => $request['aml_decision'] ?? '',
                    'notes' => trim($request['notes']) ?? ''
                ]);
            }

            $clientFullName = $updateQuote->first_name.' '.$updateQuote->last_name;

            return [$quoteStatus->text, $updateQuote->code, $quoteType->text, $updateQuote->pa_id, $clientFullName];
        } else {
            return 'false';
        }
    }
}
