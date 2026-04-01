<?php

namespace App\Services\AML;

use App\Enums\quoteTypeCode;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\QuoteType;

class AMLBusinessPayloadService
{
    public function getBusinessPayload(QuoteType $quoteType, object $quoteRequest): array
    {
        if ($quoteType->code !== quoteTypeCode::Business) {
            return [];
        }

        return [
            'businessTypeCode' => $this->getBusinessTypeCode($quoteRequest),
            'businessCoverTypeText' => $this->getBusinessCoverTypeText($quoteRequest),
            'businessCommuModeText' => $this->getBusinessCommunicationModeText($quoteRequest),
        ];
    }

    private function getBusinessTypeCode(object $quoteRequest): ?string
    {
        if (! isset($quoteRequest->business_type_of_insurance_id)) {
            return null;
        }

        return BusinessQuoteType::where('id', $quoteRequest->business_type_of_insurance_id)
            ->value('code');
    }

    private function getBusinessCoverTypeText(object $quoteRequest): ?string
    {
        if (! isset($quoteRequest->business_cover_type_id)) {
            return null;
        }

        return BusinessCoverType::where('id', $quoteRequest->business_cover_type_id)
            ->value('text');
    }

    private function getBusinessCommunicationModeText(object $quoteRequest): ?string
    {
        if (! isset($quoteRequest->business_communication_mode_id)) {
            return null;
        }

        return CommunicationMode::where('id', $quoteRequest->business_communication_mode_id)
            ->value('text');
    }
}
