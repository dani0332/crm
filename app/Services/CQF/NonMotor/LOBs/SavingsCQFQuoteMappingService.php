<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class SavingsCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::SAVINGS;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Savings;
    }

    protected function getProductName(): string
    {
        return 'Savings insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $savingsQuote = $quote->savingsQuote;

        return [
            'investment_amount' => $savingsQuote?->investment_amount ?? null,
        ];
    }
}
