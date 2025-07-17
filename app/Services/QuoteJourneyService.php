<?php

namespace App\Services;

use App\Enums\QuoteJourneyEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Services\Logger\LoggerService;

class QuoteJourneyService
{
    /**
     * Generate dynamic quote journey templates based on quote type ID
     */
    public function generateQuoteJourneyTemplates($quoteTypeId)
    {
        $quoteTypeName = QuoteTypes::getName($quoteTypeId)->value;

        return [
            'leadCreated' => "Information for $quoteTypeName insurance quote provided",
            'planSelected' => "Plan selected for $quoteTypeName insurance",
            'paymentMade' => 'Add-ons selected and payment made',
            'documentUpload' => 'Documents upload',
            'policyIssued' => 'Policy issuance',
        ];
    }

    /**
     * Call CAPI v1-quote-journey
     */
    public function sendQuoteJourneyToCapi($quoteUUID, $quoteTypeId, $quoteJourneyEntries)
    {
        if ($quoteTypeId == QuoteTypeId::Car) {
            LoggerService::startQuoteLogging($quoteUUID);
            LoggerService::info('Sending quote journey to CAPI', ['quoteTypeId' => $quoteTypeId]);
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

    /**
     * Policy Issued Quote Journey
     */
    public function policyIssuedQuoteJourney($quoteUUID, $quoteTypeId, $status = QuoteJourneyEnum::COMPLETED)
    {
        $quoteJourneyTemplates = $this->generateQuoteJourneyTemplates($quoteTypeId);
        $quoteJourneyEntries = [
            [
                'status' => $status,
                'text' => $quoteJourneyTemplates['policyIssued'],
            ],
        ];
        $this->sendQuoteJourneyToCapi($quoteUUID, $quoteTypeId, $quoteJourneyEntries);
    }

    /**
     * Lead Created Quote Journey
     */
    public function leadCreatedQuoteJourney($quoteUUID, $quoteTypeId)
    {
        $quoteJourneyTemplates = $this->generateQuoteJourneyTemplates($quoteTypeId);
        $quoteJourneyEntries = [
            [
                'status' => QuoteJourneyEnum::COMPLETED,
                'text' => $quoteJourneyTemplates['leadCreated'],
            ],
            [
                'status' => QuoteJourneyEnum::IN_PROCESS,
                'text' => $quoteJourneyTemplates['planSelected'],
            ],
            [
                'status' => QuoteJourneyEnum::PENDING,
                'text' => $quoteJourneyTemplates['paymentMade'],
            ],
            [
                'status' => QuoteJourneyEnum::PENDING,
                'text' => $quoteJourneyTemplates['documentUpload'],
            ],
            [
                'status' => QuoteJourneyEnum::PENDING,
                'text' => $quoteJourneyTemplates['policyIssued'],
            ],
        ];
        $this->sendQuoteJourneyToCapi($quoteUUID, $quoteTypeId, $quoteJourneyEntries);
    }
}
