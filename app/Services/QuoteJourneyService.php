<?php

namespace App\Services;

use App\Enums\QuoteJourneyEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Services\Logger\LoggerService;

class QuoteJourneyService
{
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
