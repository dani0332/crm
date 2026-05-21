<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\LookupRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BikeCQFQuoteMappingService extends BaseCQFQuoteMappingService
{
    protected function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::BIKE;
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Bike;
    }

    protected function getProductName(): string
    {
        return 'Bike insurance';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getFailedQuoteDataExtra(PersonalQuote $quote): array
    {
        $bikeQuote = $quote->bikeQuote;
        $make = $bikeQuote?->bikeMake?->text ?? null;
        $model = $bikeQuote?->bikeModel?->text ?? null;

        return [
            'make' => $make,
            'model' => $model,
            'year' => $bikeQuote?->year_of_manufacture ?? null,
        ];
    }

    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        if ($quote instanceof CarQuote) {
            return $this->mapRenewalQuoteFromCarQuote($quote, $renewalsUploadLeads, $quoteUuid);
        }

        return parent::mapRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);
    }

    public function mapFailedQuoteData(Model $quote): array
    {
        if ($quote instanceof CarQuote) {
            return $this->mapFailedQuoteDataFromCarQuote($quote);
        }

        return parent::mapFailedQuoteData($quote);
    }

    /**
     * Map CarQuote (vehicle_type_id = Bike) to renewal quote data for Bike LOB.
     *
     * @return array<string, mixed>
     */
    protected function mapRenewalQuoteFromCarQuote(CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        $renewalBatchId = self::getRenewalBatchIdForDate($quote->policy_expiry_date);
        $shortCode = str_replace('-', '', QuoteTypes::BIKE->shortCode());

        $quote->loadMissing('payments');

        $quoteData = [
            'customer_id' => $quote->getAttribute('customer_id'),
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => sprintf('%s-%s', $shortCode, $quoteUuid),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'dob' => $quote->dob ?? null,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch' => null,
            'renewal_batch_id' => $renewalBatchId,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $renewalsUploadLeads->renewal_import_code,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date ?? null,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->premium ?? null,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($quote->payments->first()),
            'previous_advisor_id' => $quote->advisor_id ?? null,
            'previous_quote_id' => PersonalQuote::where('uuid', $quote->uuid)->value('id'),
            'quote_type_id' => QuoteTypeId::Bike,
            'nationality_id' => $quote->nationality_id ?? null,
            'currently_insured_with_id' => $quote->insurance_provider_id ?? null,
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
     * @return array<string, mixed>
     */
    protected function mapFailedQuoteDataFromCarQuote(CarQuote $quote): array
    {
        $make = $quote->carMake?->text ?? null;
        $model = $quote->carModel?->text ?? null;
        $year = $quote->getAttribute('Year_of_manufacture') ?? $quote->getAttribute('year_of_manufacture') ?? null;

        $quote->loadMissing('payments');

        return [
            'customer_name' => trim($quote->first_name.' '.($quote->last_name ?? '')),
            'email' => $quote->email ?? null,
            'mobile_no' => $quote->mobile_no ?? null,
            'quote_type' => str_replace('-', '', QuoteTypes::BIKE->shortCode()),
            'insurer' => $quote->insuranceProvider?->text ?? null,
            'product' => 'Bike insurance',
            'product_type' => null,
            'advisor' => null,
            'policy_number' => $quote->policy_number ?? null,
            'start_date' => $quote->policy_start_date ? Carbon::parse($quote->policy_start_date)->format(config('constants.DATE_DISPLAY_SLASH_FORMAT')) : null,
            'end_date' => $quote->policy_expiry_date ? Carbon::parse($quote->policy_expiry_date)->format(config('constants.DATE_DISPLAY_SLASH_FORMAT')) : null,
            'batch' => null,
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'previous_advisor' => $quote->advisor?->email ?? null,
            'previous_quote_policy_premium' => $quote->premium ?? null,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($quote->payments->first()),
            'previous_ref_id' => $quote->code ?? null,
            'source' => $quote->source ?? null,
            'notes' => $quote->notes ?? null,
            'plan_name' => null,
            'errors' => $quote->validation_errors ?? null,
        ];
    }
}
