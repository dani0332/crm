<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class BusinessCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::BUSINESS;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Business;
    }

    protected function getProductName(): string
    {
        return 'Business insurance';
    }

    protected function resolveLobPayment(PersonalQuote $quote): ?object
    {
        $quote->loadMissing('businessQuote.payments');

        return $quote->businessQuote?->payments->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        return [];
    }
}
