<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
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
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\PetCQFValidationService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\YachtCQFValidationService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

/**
 * Registry of LOB-specific CQF services for Non-motor renewals (PersonalQuote-based LOBs).
 * Only quote types listed in {@see self::$lobMap} are supported; others resolve via {@see hasLOB()}.
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
        QuoteTypes::BUSINESS->value => [
            'validator' => BusinessCQFValidationService::class,
            'mapper' => BusinessCQFQuoteMappingService::class,
            'storage' => BusinessCQFQuoteStorageService::class,
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

    /**
     * The single authoritative eligibility filter for Non-motor CQF renewals.
     * Excludes cancelled/pending-cancellation quotes; requires a paid/captured/credit payment status.
     * Used by both the orchestrator count query and the per-LOB quote dispatch query.
     *
     * @return array{excluded_quote_status: array<int>, payment_status: array<int>}
     */
    public static function eligibilityFilter(): array
    {
        return [
            'excluded_quote_status' => [
                QuoteStatusEnum::PolicyCancelled,
                QuoteStatusEnum::PolicyCancelledReissued,
                QuoteStatusEnum::CancellationPending,
            ],
            'payment_status' => [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PARTIAL_CAPTURED,
                PaymentStatusEnum::CREDIT_APPROVED,
            ],
        ];
    }

    /**
     * Apply the correct payment-status eligibility filter for the given LOB.
     *
     * For BUSINESS, payment status lives on `business_quote_request` (joined via
     * `business_quote_request.id = personal_quotes.quote_id`), not on `personal_quotes`.
     * All other LOBs store payment status directly on `personal_quotes`.
     *
     * @param  array{excluded_quote_status: array<int>, payment_status: array<int>}  $filter
     */
    public static function applyPaymentStatusFilter(Builder $query, QuoteTypes $quoteType, array $filter): Builder
    {
        if ($quoteType === QuoteTypes::BUSINESS) {
            return $query->whereHas('businessQuote', fn (Builder $sub) => $sub->whereIn('payment_status_id', $filter['payment_status'])
            );
        }

        return $query->whereIn('payment_status_id', $filter['payment_status']);
    }
}
