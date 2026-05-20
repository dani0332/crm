<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class QuoteStatusService
{
    use GenericQueriesAllLobs;

    public function updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType, $notes = null)
    {
        info('fn updateQuoteStatus started, quoteTypeId: '.$quoteTypeId.', quoteRequestId: '.$quoteRequestId);
        $AMLService = app(AMLService::class);
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteStatus = QuoteStatus::where('code', $quoteStatusType)->firstOrFail();
        $updateQuote = $this->getQuoteObjectBy($quoteType->code, $quoteRequestId, 'uuid');

        if (checkPersonalQuotes($quoteType->code) && (! $AMLService->isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteRequestId = $AMLService->getPersonalQuoteId($quoteTypeId, $quoteRequestId);
            $AMLService->updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, true, ['quote_status_id' => $quoteStatus->id]);
        }

        $previousStatusId = $updateQuote->quote_status_id;
        $currentStatusId = $quoteStatus->id;

        $quoteStatusLog = QuoteStatusLog::create([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'current_quote_status_id' => $currentStatusId,
            'previous_quote_status_id' => $previousStatusId,
            'status_change_source' => LeadSourceEnum::IMCRM,
            'notes' => $notes ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $quoteStatusLog;
    }

    public function markQuoteAsStale($quoteTypeId, $quoteRequestId)
    {
        $quoteType = QuoteType::findOrFail($quoteTypeId);
        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteRequestId);

        if (! empty($updateQuote->quote_status_id)) {
            switch ($updateQuote->quote_status_id) {
                case QuoteStatusEnum::NewLead:
                    $updateQuote->quote_status_id = QuoteStatusEnum::Quoted;
                    $updateQuote->save();
                    $personalQuote = app(PersonalQuoteService::class)->getEntity($quoteTypeId, $quoteRequestId);
                    $personalQuote->quote_status_id = QuoteStatusEnum::Quoted;
                    $personalQuote->save();

                    return $updateQuote;
                case QuoteStatusEnum::Quoted:
                    if (in_array(request('workflow_type'), [
                        QuoteFlowType::LIFE_REVIVAL_FOLLOWUPS->label(),
                        QuoteFlowType::HOME_REVIVAL_FOLLOWUP->label(),
                    ], true)) {
                        $updateQuote->quote_status_id = match (request('workflow_transition')) {
                            'lost' => QuoteStatusEnum::Lost,
                            'followed_up' => QuoteStatusEnum::FollowedUp,
                            default => QuoteStatusEnum::FollowedUp,
                        };
                    }

                    if (in_array(request('workflow_type'), [
                        QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->label(),
                        QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS->label(),
                    ], true)) {
                        $updateQuote->quote_status_id = QuoteStatusEnum::FollowedUp;
                    } else {
                        $updateQuote->quote_status_id = QuoteStatusEnum::Stale;
                    }
                    break;
                case QuoteStatusEnum::FollowedUp:
                    if (request('workflow_type') == QuoteFlowType::LIFE_REVIVAL_FOLLOWUPS->label()) {
                        $updateQuote->quote_status_id = QuoteStatusEnum::Lost;
                    } else {
                        $updateQuote->quote_status_id = QuoteStatusEnum::Stale;
                    }
                    break;
                default:
                    return $updateQuote;
            }
            $updateQuote->save();

            return $updateQuote;
        }

        return $updateQuote;
    }

    /**
     * Check if a policy sent log exists for the given quote.
     *
     * @param  int  $quoteId  The ID of the quote to check.
     * @return bool True if a policy sent log exists, false otherwise.
     */
    public function isPolicySentLogExists(int $quoteId): bool
    {
        // Check if a policy sent log exists for the given quote.
        return QuoteStatusLog::where('quote_request_id', $quoteId)
            ->where(function ($query) {
                $query->where('previous_quote_status_id', QuoteStatusEnum::PolicySentToCustomer)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PolicySentToCustomer);
            })
            ->select('id')
            ->limit(1)
            ->exists();
    }
}
