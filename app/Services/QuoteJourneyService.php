<?php

namespace App\Services;

use App\Enums\QuoteJourneyEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Models\QuoteJourney;
use App\Services\Logger\LoggerService;

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
