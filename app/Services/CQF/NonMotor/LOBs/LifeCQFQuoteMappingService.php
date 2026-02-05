<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class LifeCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::LIFE;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Life;
    }

    protected function getProductName(): string
    {
        return 'Life insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $lifeQuote = $quote->lifeQuote;

        return [
            'sum_insured_value' => $lifeQuote?->sum_insured_value ?? null,
        ];
    }
}
