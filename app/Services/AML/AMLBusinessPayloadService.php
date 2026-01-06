<?php

namespace App\Services\AML;

use App\Enums\quoteTypeCode;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;

/**
 * Service for handling business-specific AML payload data
 * Extracts business quote type, cover type, and communication mode information
 */
class AMLBusinessPayloadService
{
    /**
     * Get business-specific payload data
     *
     * @param QuoteType $quoteType
     * @param object $quoteRequest
     * @return array
     */
    public function getBusinessPayload(QuoteType $quoteType, object $quoteRequest): array
    {
        if ($quoteType->code !== quoteTypeCode::Business) {
            return [];
        }

        LoggerService::info('Business lead found against AML Screening');

        return [
            'businessTypeCode' => $this->getBusinessTypeCode($quoteRequest),
            'businessCoverTypeText' => $this->getBusinessCoverTypeText($quoteRequest),
            'businessCommuModeText' => $this->getBusinessCommunicationModeText($quoteRequest),
        ];
    }

    /**
     * Get business type code
     *
     * @param object $quoteRequest
     * @return string|null
     */
    private function getBusinessTypeCode(object $quoteRequest): ?string
    {
        if (!isset($quoteRequest->business_type_of_insurance_id)) {
            return null;
        }

        return BusinessQuoteType::where('id', $quoteRequest->business_type_of_insurance_id)
            ->value('code');
    }

    /**
     * Get business cover type text
     *
     * @param object $quoteRequest
     * @return string|null
     */
    private function getBusinessCoverTypeText(object $quoteRequest): ?string
    {
        if (!isset($quoteRequest->business_cover_type_id)) {
            return null;
        }

        return BusinessCoverType::where('id', $quoteRequest->business_cover_type_id)
            ->value('text');
    }

    /**
     * Get business communication mode text
     *
     * @param object $quoteRequest
     * @return string|null
     */
    private function getBusinessCommunicationModeText(object $quoteRequest): ?string
    {
        if (!isset($quoteRequest->business_communication_mode_id)) {
            return null;
        }

        return CommunicationMode::where('id', $quoteRequest->business_communication_mode_id)
            ->value('text');
    }
}

