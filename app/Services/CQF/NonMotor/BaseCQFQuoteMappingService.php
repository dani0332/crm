<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\RenewalBatch;
use App\Models\RenewalsUploadLeads;
use App\Repositories\LookupRepository;
use App\Services\CapiRequestService;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Base mapping service for Non-motor CQF renewal quotes (PersonalQuote-based LOBs).
 * Subclasses define quote type, product name, and LOB-specific failed-export fields.
 */
abstract class BaseCQFQuoteMappingService implements CQFQuoteMappingInterface
{
    /**
     * Resolve renewal batch ID for a given date (ISO week/year, same logic as RenewalsUploadService::validateBatch).
     */
    public static function getRenewalBatchIdForDate(Carbon|string $date): ?int
    {
        $endDateObj = $date instanceof Carbon ? $date : Carbon::parse($date);

        $isoWeek = $endDateObj->isoWeek;
        $isoYear = $endDateObj->isoWeekYear;
        $weekNumber = 'W'.$isoWeek;

        $batch = RenewalBatch::where([
            ['name', $weekNumber.'-'.$isoYear],
            ['quote_type_id', null],
        ])->first();

        if (! $batch) {
            $calendarYear = $endDateObj->year;
            $batch = RenewalBatch::where([
                ['name', $weekNumber.'-'.$calendarYear],
                ['quote_type_id', null],
            ])->first();
        }

        return $batch?->id;
    }

    abstract protected function getQuoteType(): QuoteTypes;

    /**
     * Quote type ID (int constant from QuoteTypeId enum).
     */
    abstract protected function getQuoteTypeId(): int;

    abstract protected function getProductName(): string;

    /**
     * LOB-specific columns for failed quote export. Merged with base failed data.
     *
     * @return array<string, mixed>
     */
    abstract protected function getFailedQuoteDataExtra(PersonalQuote $quote): array;

    /**
     * Generate UUID for new renewal quote (from CAPI). Aligned with mapRenewalQuote which uses this UUID.
     */
    public function generateUUID(): ?string
    {
        $quoteType = $this->getQuoteType();

        if (checkPersonalQuotes($quoteType->value)) {
            $response = app(CapiRequestService::class)->getPersonalQuoteUUID($quoteType->id());
        } else {
            $response = app(CapiRequestService::class)->getUUID($quoteType->id());
        }

        return $response?->uuid ?? null;
    }

    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        if (! $quote instanceof PersonalQuote) {
            return [];
        }

        return $this->buildBaseQuoteDataFromPersonalQuote($quote, $renewalsUploadLeads, $quoteUuid);
    }

    public function mapFailedQuoteData(Model $quote): array
    {
        if (! $quote instanceof PersonalQuote) {
            return [];
        }

        return $this->buildBaseFailedQuoteDataFromPersonalQuote($quote);
    }

    /**
     * Build common renewal quote data from PersonalQuote. Used by all PersonalQuote-based LOBs.
     *
     * @return array<string, mixed>
     */
    protected function buildBaseQuoteDataFromPersonalQuote(
        PersonalQuote $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        string $quoteUuid
    ): array {
        $quote->loadMissing('payments');
        $renewalBatchId = self::getRenewalBatchIdForDate($quote->policy_expiry_date);
        $shortCode = str_replace('-', '', $this->getQuoteType()->shortCode());

        $payment = $quote->payments?->first() ?? $this->resolveLobPayment($quote);

        $quoteData = [
            'customer_id' => $quote->customer_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => sprintf('%s-%s', $shortCode, $quoteUuid),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'dob' => $quote->dob,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch' => null,
            'renewal_batch_id' => $renewalBatchId,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $renewalsUploadLeads->renewal_import_code,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->price_with_vat,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($payment),
            'previous_advisor_id' => $quote->advisor_id,
            'previous_quote_id' => $quote->id,
            'quote_type_id' => $this->getQuoteTypeId(),
            'nationality_id' => $quote->nationality_id,
            'currently_insured_with_id' => $quote->insurance_provider_id,
            'insurance_provider_id' => null,
        ];

        $lookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)
            ->first();

        if ($lookup) {
            $quoteData['transaction_type_id'] = $lookup->id;
        }

        return $quoteData;
    }

    /**
     * Build common failed quote export data from PersonalQuote, merged with LOB-specific extra.
     *
     * @return array<string, mixed>
     */
    protected function buildBaseFailedQuoteDataFromPersonalQuote(PersonalQuote $quote): array
    {
        $quote->loadMissing('payments');

        $payment = $quote->payments?->first() ?? $this->resolveLobPayment($quote);

        $base = [
            'customer_name' => trim($quote->first_name.' '.($quote->last_name ?? '')),
            'email' => $quote->email ?? null,
            'mobile_no' => $quote->mobile_no ?? null,
            'quote_type' => str_replace('-', '', $this->getQuoteType()->shortCode()),
            'insurer' => $quote->insuranceProvider?->code ?? $quote->currentlyInsuredWith?->code ?? null,
            'product' => $this->getProductName(),
            'product_type' => null,
            'advisor' => null,
            'policy_number' => $quote->policy_number ?? null,
            'start_date' => $quote->policy_start_date ? Carbon::parse($quote->policy_start_date)->format('d/m/Y') : null,
            'end_date' => $quote->policy_expiry_date ? Carbon::parse($quote->policy_expiry_date)->format('d/m/Y') : null,
            'batch' => null,
            'previous_advisor' => $quote->advisor?->email ?? null,
            'previous_quote_policy_premium' => $quote->price_with_vat ?? null,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($payment),
            'previous_ref_id' => $quote->code ?? null,
            'source' => $quote->source ?? null,
            'notes' => $quote->notes ?? null,
            'plan_name' => null,
            'errors' => $quote->validation_errors ?? null,
        ];

        return array_merge($base, $this->getFailedQuoteDataExtra($quote));
    }

    /**
     * Override in subclasses where payments are attached to the LOB model rather than PersonalQuote.
     */
    protected function resolveLobPayment(PersonalQuote $quote): ?object
    {
        return null;
    }

    /**
     * Mirrors the Vue BookingDetails calculateCommission formula:
     * total_commission = commission_vat_not_applicable + commission_vat_applicable + commission_vat
     * Falls back to the stored commission column if breakdown fields are absent.
     */
    protected function resolveTotalCommission(?object $payment): ?float
    {
        if ($payment === null) {
            return null;
        }

        $vatApplicable = $payment->commission_vat_applicable;
        $vatNotApplicable = $payment->commission_vat_not_applicable;
        $vatOnCommission = $payment->commission_vat;

        if ($vatApplicable !== null || $vatNotApplicable !== null || $vatOnCommission !== null) {
            return (float) ($vatApplicable ?? 0) + (float) ($vatNotApplicable ?? 0) + (float) ($vatOnCommission ?? 0);
        }

        return $payment->commission !== null ? (float) $payment->commission : null;
    }
}
