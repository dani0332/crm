<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\QuoteTypes;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\CQF\Contracts\CQFValidationInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Registry of LOB-specific CQF services for Non-motor renewals (PersonalQuote-based LOBs).
 * Excludes Health (Phase 1) and Business (runs from BusinessQuote, not PersonalQuote).
 */
class NonMotorCQFRegistry
{
    /** @var array<string, array{validator: class-string, mapper: class-string, storage: class-string}> */
    protected static array $lobMap = [
        QuoteTypes::BIKE->value => [
            'validator' => \App\Services\CQF\NonMotor\LOBs\BikeCQFValidationService::class,
            'mapper' => \App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteMappingService::class,
            'storage' => \App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteStorageService::class,
        ],
        // QuoteTypes::YACHT->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\YachtCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::JETSKI->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\JetskiCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\JetskiCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\JetskiCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::CYCLE->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\CycleCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\CycleCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\CycleCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::PET->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\PetCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\PetCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\PetCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::HOME->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\HomeCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\HomeCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\HomeCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::LIFE->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\LifeCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\LifeCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\LifeCQFQuoteStorageService::class,
        // ],
        // QuoteTypes::SAVINGS->value => [
        //     'validator' => \App\Services\CQF\NonMotor\LOBs\SavingsCQFValidationService::class,
        //     'mapper' => \App\Services\CQF\NonMotor\LOBs\SavingsCQFQuoteMappingService::class,
        //     'storage' => \App\Services\CQF\NonMotor\LOBs\SavingsCQFQuoteStorageService::class,
        // ],
    ];

    public function __construct(
        protected Container $container
    ) {}

    public function getValidator(QuoteTypes $quoteType): ?CQFValidationInterface
    {
        $config = self::$lobMap[$quoteType->value] ?? null;

        if ($config === null) {
            return null;
        }

        return $this->container->make($config['validator']);
    }

    public function getMapper(QuoteTypes $quoteType): ?CQFQuoteMappingInterface
    {
        $config = self::$lobMap[$quoteType->value] ?? null;

        if ($config === null) {
            return null;
        }

        return $this->container->make($config['mapper']);
    }

    public function getStorage(QuoteTypes $quoteType): ?CQFQuoteStorageInterface
    {
        $config = self::$lobMap[$quoteType->value] ?? null;

        if ($config === null) {
            return null;
        }

        return $this->container->make($config['storage']);
    }

    /**
     * LOBs supported for Non-motor CQF Phase 1 (PersonalQuote-based, excluding Health and Business).
     * Add LOBs to $lobMap when their validator/mapper/storage classes are implemented.
     *
     * @return array<int, QuoteTypes>
     */
    public static function supportedLOBs(): array
    {
        return array_map(
            fn (string $value) => QuoteTypes::from($value),
            array_keys(self::$lobMap)
        );
    }

    public function hasLOB(QuoteTypes $quoteType): bool
    {
        return isset(self::$lobMap[$quoteType->value]);
    }
}
