<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Enums\QuoteTypes;
use App\Strategies\Validation\CorplineAllocationValidationStrategy;
use App\Strategies\Validation\GroupMedicalAllocationValidationStrategy;
use App\Strategies\Validation\HomeAllocationValidationStrategy;
use App\Strategies\Validation\LifeAllocationValidationStrategy;
use App\Strategies\Validation\SavingsAllocationValidationStrategy;
use App\Strategies\Validation\SimpleAllocationValidationStrategy;
use InvalidArgumentException;

class AllocationValidationStrategyFactory
{
    public static function create(QuoteTypes $quoteType): AllocationValidationStrategyInterface
    {
        return match ($quoteType) {
            QuoteTypes::SAVINGS => new SavingsAllocationValidationStrategy,
            QuoteTypes::HOME => new HomeAllocationValidationStrategy,
            QuoteTypes::LIFE => new LifeAllocationValidationStrategy,
            QuoteTypes::PET => new SimpleAllocationValidationStrategy(QuoteTypes::PET),
            QuoteTypes::YACHT => new SimpleAllocationValidationStrategy(QuoteTypes::YACHT),
            QuoteTypes::CYCLE => new SimpleAllocationValidationStrategy(QuoteTypes::CYCLE),
            QuoteTypes::CORPLINE => new CorplineAllocationValidationStrategy,
            QuoteTypes::GROUP_MEDICAL => new GroupMedicalAllocationValidationStrategy,
            default => throw new InvalidArgumentException("No validation strategy found for quote type: {$quoteType->value}"),
        };
    }
}
