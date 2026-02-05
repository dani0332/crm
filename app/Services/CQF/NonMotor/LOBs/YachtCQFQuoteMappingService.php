<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class YachtCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::YACHT;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Yacht;
    }

    protected function getProductName(): string
    {
        return 'Yacht insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $yachtQuote = $quote->yachtQuote;

        return [
            'boat_details' => $yachtQuote?->boat_details ?? null,
        ];
    }
}
