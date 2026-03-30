<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\QuoteTypes;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\CQF\Contracts\CQFValidationInterface;
use App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\BikeCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\BusinessCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\BusinessCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\BusinessCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\CycleCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\CycleCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\CycleCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\HomeCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\HomeCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\HomeCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\JetskiCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\JetskiCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\JetskiCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\LifeCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\LifeCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\LifeCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\PetCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\SavingsCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\SavingsCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\SavingsCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFValidationService;
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
            'validator' => BikeCQFValidationService::class,
            'mapper' => BikeCQFQuoteMappingService::class,
            'storage' => BikeCQFQuoteStorageService::class,
        ],
        QuoteTypes::YACHT->value => [
            'validator' => YachtCQFValidationService::class,
            'mapper' => YachtCQFQuoteMappingService::class,
            'storage' => YachtCQFQuoteStorageService::class,
        ],
        QuoteTypes::JETSKI->value => [
            'validator' => JetskiCQFValidationService::class,
            'mapper' => JetskiCQFQuoteMappingService::class,
            'storage' => JetskiCQFQuoteStorageService::class,
        ],
        QuoteTypes::CYCLE->value => [
            'validator' => CycleCQFValidationService::class,
            'mapper' => CycleCQFQuoteMappingService::class,
            'storage' => CycleCQFQuoteStorageService::class,
        ],
        QuoteTypes::PET->value => [
            'validator' => PetCQFValidationService::class,
            'mapper' => PetCQFQuoteMappingService::class,
            'storage' => PetCQFQuoteStorageService::class,
        ],
        QuoteTypes::HOME->value => [
            'validator' => HomeCQFValidationService::class,
            'mapper' => HomeCQFQuoteMappingService::class,
            'storage' => HomeCQFQuoteStorageService::class,
        ],
        QuoteTypes::LIFE->value => [
            'validator' => LifeCQFValidationService::class,
            'mapper' => LifeCQFQuoteMappingService::class,
            'storage' => LifeCQFQuoteStorageService::class,
        ],
        QuoteTypes::BUSINESS->value => [
            'validator' => BusinessCQFValidationService::class,
            'mapper' => BusinessCQFQuoteMappingService::class,
            'storage' => BusinessCQFQuoteStorageService::class,
        ],
        QuoteTypes::SAVINGS->value => [
            'validator' => SavingsCQFValidationService::class,
            'mapper' => SavingsCQFQuoteMappingService::class,
            'storage' => SavingsCQFQuoteStorageService::class,
        ],
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
     * LOBs supported for Non-motor CQF Phase 1 (PersonalQuote-based, excluding Health).
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
