<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\AMLStatusCode;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\Contracts\CQFQuoteStorageInterface;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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
        array &$epCodes = []
    ): ?Model {
        if (! $quote instanceof PersonalQuote) {
            return null;
        }

        LoggerService::info(self::class.' - Storing '.$this->getLobName().' CQF renewal quote');

        $quoteUuid = $this->mappingService->generateUUID();
        if ($quoteUuid === null) {
            LoggerService::error(self::class.' - Failed to generate UUID for '.$this->getLobName().' renewal quote');

            return null;
        }

        $quoteData = $this->mappingService->mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);

        return DB::transaction(function () use ($quoteData, $quote, &$epCodes) {
            $newQuote = PersonalQuote::create($quoteData);
            $newQuote->quoteDetail()->create([]);
            $this->copyLobQuoteDetail($newQuote, $quote);
            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, $this->getQuoteTypeId());
            $this->collectEmbeddedProductCodes($quote, $newQuote, $epCodes);

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
     * Optional hook for LOBs that support embedded products (e.g. Bike RDX). Base does nothing; override to collect ep codes from old quote into $epCodes.
     *
     * @param  array<int, string>  $epCodes
     */
    protected function collectEmbeddedProductCodes(Model $oldQuote, PersonalQuote $newQuote, array &$epCodes): void {}
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

    /**
     * After copying a LOB quote row from the previous policy, align lead fields with the new renewal PersonalQuote
     * (source, status, advisor, assignment, renewal batch, previous-policy references) and clear stale
     * plan/policy detail fields that belong to the old issued policy.
     *
     * Only updates keys present in $data so we do not insert columns absent from the copied payload (and target table).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function alignCopiedLobRowWithRenewalPersonalQuote(array $data, PersonalQuote $newQuote, ?Model $oldLobQuote = null): array
    {
        // Fields inherited from the new renewal PersonalQuote (lead/assignment + previous-policy references).
        $fromNewQuote = [
            'source',
            'quote_status_id',
            'advisor_id',
            'assignment_type',
            'renewal_batch_id',
            'previous_quote_policy_number',
            'previous_quote_policy_premium',
            'previous_quote_policy_commission',
            'previous_advisor_id',
            'transaction_approved_at',
            'transaction_type_id',
        ];

        foreach ($fromNewQuote as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $newQuote->$field;
            }
        }

        if (array_key_exists('previous_policy_start_date', $data)) {
            $data['previous_policy_start_date'] = $newQuote->previous_policy_start_date
                ? Carbon::parse($newQuote->previous_policy_start_date)->format(config('constants.DATE_FORMAT_ONLY'))
                : null;
        }

        if (array_key_exists('previous_policy_expiry_date', $data)) {
            $data['previous_policy_expiry_date'] = $newQuote->previous_policy_expiry_date
                ? Carbon::parse($newQuote->previous_policy_expiry_date)->format(config('constants.DATE_FORMAT_ONLY'))
                : null;
        }

        if (array_key_exists('aml_status', $data)) {
            $data['aml_status'] = AMLStatusCode::AMLPending;
        }

        // previous_quote_id references the old LOB row's own table, not PersonalQuote.
        if (array_key_exists('previous_quote_id', $data)) {
            $data['previous_quote_id'] = $oldLobQuote?->id;
        }

        // Plan / policy detail fields — null out stale values from the old issued policy.
        // The new renewal quote starts without an insurer, pricing, or issued policy details.
        $nullableFields = [
            'insurance_provider_id',
            'price_vat_applicable',
            'price_vat_not_applicable',
            'price_with_vat',
            'insurer_quote_number',
            'policy_number',
            'policy_issuance_date',
            'policy_issuance_status_id',
            'policy_start_date',
            'policy_expiry_date',
            'kyc_decision',
        ];

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
