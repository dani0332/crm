<?php

namespace App\Services;

use App\Enums\QuoteJourneyEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\QuoteJourney;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;

class QuoteJourneyService
{
    public function completePolicyIssuanceEntry(string $quoteUUID, int $quoteTypeId): void
    {
        $quoteJourneyEntry = QuoteJourney::query()
            ->where('quote_uuid', $quoteUUID)
            ->where('quote_type_id', $quoteTypeId)
            ->where('text', 'like', '%'.QuoteJourneyEnum::POLICY_ISSUANCE.'%')
            ->latest('id')
            ->first();

        LoggerService::info('QuoteJourneyService - fetched quote journey entry for quote uuid: '.$quoteUUID, extra: [
            'quote_uuid' => $quoteUUID,
            'quote_type_id' => $quoteTypeId,
            'quote_journey_id' => $quoteJourneyEntry?->id,
            'current_status' => $quoteJourneyEntry?->status,
            'text' => $quoteJourneyEntry?->text,
        ]);

        if (! $quoteJourneyEntry) {
            LoggerService::warning('QuoteJourneyService - no quote journey entry found for quote uuid: '.$quoteUUID, [
                'quote_uuid' => $quoteUUID,
                'quote_type_id' => $quoteTypeId,
            ]);

            return;
        }

        $wasUpdated = $quoteJourneyEntry->update([
            'status' => QuoteJourneyEnum::COMPLETED,
        ]);

        LoggerService::info('QuoteJourneyService - attempted quote journey update for quote uuid: '.$quoteUUID, extra: [
            'quote_uuid' => $quoteUUID,
            'quote_type_id' => $quoteTypeId,
            'quote_journey_id' => $quoteJourneyEntry->id,
            'update_successful' => $wasUpdated,
            'updated_status' => $quoteJourneyEntry->fresh()?->status,
        ]);

        if ($wasUpdated) {
            LoggerService::info('QuoteJourneyService - completed quote journey entry for quote uuid: '.$quoteUUID, extra: [
                'quote_uuid' => $quoteUUID,
                'quote_type_id' => $quoteTypeId,
                'quote_journey_id' => $quoteJourneyEntry->id,
            ]);
        }
    }

    /**
     * After all required receive-from-customer documents are present: complete the prior journey row and move policy issuance to in process.
     */
    public function advanceAfterRequiredCustomerDocuments(string $quoteUUID, int $quoteTypeId): void
    {
        // Only consider a prior "Documents uploaded" entry when it is in a state that can be advanced (PENDING or IN_PROCESS).
        $priorEntry = QuoteJourney::query()
            ->where('quote_uuid', $quoteUUID)
            ->where('quote_type_id', $quoteTypeId)
            ->where('text', QuoteJourneyEnum::DOCUMENT_UPLOADED)
            ->whereIn('status', [QuoteJourneyEnum::PENDING, QuoteJourneyEnum::IN_PROCESS])
            ->latest('id')
            ->first();

        if (! $priorEntry) {
            LoggerService::warning('QuoteJourneyService - no prior journey row in PENDING/IN_PROCESS for required customer documents; skipping advancement', [
                'quote_uuid' => $quoteUUID,
                'quote_type_id' => $quoteTypeId,
            ]);

            // Prior step missing or not in an actionable status — skip updating policy issuance to avoid reactivating cancelled/completed journeys.
            return;
        }

        // Fetch policy issuance entry before performing updates so both changes can be done atomically.
        $policyIssuanceEntry = QuoteJourney::query()
            ->where('quote_uuid', $quoteUUID)
            ->where('quote_type_id', $quoteTypeId)
            ->where('text', QuoteJourneyEnum::POLICY_ISSUANCE)
            ->latest('id')
            ->first();

        if (! $policyIssuanceEntry) {
            LoggerService::warning('QuoteJourneyService - no policy issuance journey row to set in process', [
                'quote_uuid' => $quoteUUID,
                'quote_type_id' => $quoteTypeId,
            ]);

            return;
        }

        if (in_array($policyIssuanceEntry->status, [QuoteJourneyEnum::COMPLETED, QuoteJourneyEnum::CANCELLED, QuoteJourneyEnum::IN_PROCESS], true)) {
            return;
        }

        // Perform both updates in a single transaction to keep journey state consistent.
        DB::transaction(function () use ($priorEntry, $policyIssuanceEntry) {
            $priorEntry->update([
                'status' => QuoteJourneyEnum::COMPLETED,
            ]);

            $policyIssuanceEntry->update([
                'status' => QuoteJourneyEnum::IN_PROCESS,
            ]);
        });

        LoggerService::info('QuoteJourneyService - completed Documents uploaded journey step and set policy issuance to in process', extra: [
            'quote_uuid' => $quoteUUID,
            'quote_type_id' => $quoteTypeId,
            'documents_uploaded_journey_id' => $priorEntry->id,
            'policy_issuance_journey_id' => $policyIssuanceEntry->id,
        ]);
    }

    /**
     * Policy Issued Quote Journey
     */
    public function policyIssuedQuoteJourney($quoteUUID, $quoteTypeId, $status = QuoteJourneyEnum::COMPLETED)
    {
        LoggerService::startQuoteLogging($quoteUUID);

        if ($quoteTypeId != QuoteTypeId::Car) {
            LoggerService::info('Quote type ID is not Car, skipping quote journey', ['quoteTypeId' => $quoteTypeId]);

            return;
        }
        LoggerService::info('Sending quote journey to CAPI', ['quoteTypeId' => $quoteTypeId]);
        $quoteJourneyEntries = [
            [
                'status' => $status,
                'text' => QuoteJourneyEnum::POLICY_ISSUANCE,
            ],
        ];
        $payload = [
            'quoteUUID' => $quoteUUID,
            'quoteTypeId' => $quoteTypeId,
            'quoteJourneyEntries' => $quoteJourneyEntries,
        ];
        LoggerService::info('Sending quote journey to CAPI Payload ', extra: ['payload' => $payload]);
        $response = Capi::request('/api/v1-quote-journey', 'post', $payload);
        LoggerService::info('Received response from CAPI for quote journey', extra: ['response' => $response]);
    }
}
