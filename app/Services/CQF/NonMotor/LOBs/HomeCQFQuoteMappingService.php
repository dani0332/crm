<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class HomeCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::HOME;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Home;
    }

    protected function getProductName(): string
    {
        return 'Home insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $homeQuote = $quote->homeQuote;

        return [
            'previous_building_aed' => $homeQuote?->previous_building_aed ?? null,
            'previous_contents_aed' => $homeQuote?->previous_contents_aed ?? null,
        ];
    }
}
