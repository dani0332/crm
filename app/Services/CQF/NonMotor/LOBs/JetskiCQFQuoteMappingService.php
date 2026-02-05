<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class JetskiCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::JETSKI;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Jetski;
    }

    protected function getProductName(): string
    {
        return 'Jetski insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        return [];
    }
}
