<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Base storage for Non-motor CQF renewal quotes. Shared logic: policy dates, UUID, map data, transaction (create PersonalQuote + quoteDetail + LOB detail + embedded product).
 * Subclasses define quote type, LOB name for logging, and copyLobQuoteDetail().
 */
abstract class BaseCQFQuoteStorageService implements CQFQuoteStorageInterface
{
    public function __construct(
        protected BaseCQFQuoteMappingService $mappingService
    ) {}

    public function storeRenewalQuote(
        Model $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes = []
    ): ?Model {
        if (! $quote instanceof PersonalQuote) {
            return null;
        }

        LoggerService::info(self::class.' - Storing '.$this->getLobName().' CQF renewal quote');

        [$policyStartDate, $newPolicyExpiryDate] = $this->computePolicyDates($quote, $renewalDaysThreshold);

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for '.$this->getLobName().' renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
        $quoteData['policy_start_date'] = $policyStartDate;
        $quoteData['policy_expiry_date'] = $newPolicyExpiryDate;

        return DB::transaction(function () use ($quoteData, $quote) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyLobQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, $this->getQuoteTypeId());

            LoggerService::info(self::class.' - '.ucfirst($this->getLobName()).' CQF renewal quote created successfully', [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
            ]);

            return $newQuote;
        });
    }

    /**
     * LOB identifier for logging (e.g. 'bike', 'home').
     */
    abstract protected function getLobName(): string;

    /**
     * Quote type for EmbeddedProductRepository (e.g. QuoteTypeId::Bike).
     */
    abstract protected function getQuoteTypeId(): int;

    /**
     * Copy LOB-specific quote detail from old quote to new PersonalQuote (e.g. bikeQuote, homeQuote).
     */
    abstract protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void;

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    protected function computePolicyDates(Model $quote, int $renewalDaysThreshold): array
    {
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        return [$policyStartDate, $newPolicyExpiryDate];
    }

    /**
     * Default copyable attributes for LOB quote tables (id, personal_quote_id, timestamps, uuid, code replaced).
     * Override in subclasses if different (e.g. Business uses quote_id; Life unsets quote_id).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributes(array $attributes, int $personalQuoteId, string $newQuoteUuid, string $newQuoteCode): array
    {
        unset(
            $attributes['id'],
            $attributes['personal_quote_id'],
            $attributes['created_at'],
            $attributes['updated_at'],
            $attributes['uuid'],
            $attributes['code']
        );
        $attributes['personal_quote_id'] = $personalQuoteId;
        $attributes['uuid'] = $newQuoteUuid;
        $attributes['code'] = $newQuoteCode;

        return $attributes;
    }
}
