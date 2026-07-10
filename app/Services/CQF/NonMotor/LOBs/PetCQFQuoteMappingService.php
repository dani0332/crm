<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;

class PetCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::PET;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Pet;
    }

    protected function getProductName(): string
    {
        return 'Pet insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $petQuote = $quote->petQuote;

        return [
            'breed_of_pet1' => $petQuote?->breed_of_pet1 ?? null,
            'pet_type_id' => $petQuote?->pet_type_id ?? null,
            'pet_age_id' => $petQuote?->pet_age_id ?? null,
        ];
    }
}
