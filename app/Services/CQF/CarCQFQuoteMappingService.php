<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\CarRegistrationType;
use App\Enums\carTypeInsuranceCode;
use App\Enums\CarTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\UAELicenseHeldForEnum;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\LookupRepository;
use App\Services\RenewalsAddonServices;
use App\Traits\ResolvesCommission;
use Illuminate\Support\Carbon;

class CarCQFQuoteMappingService
{
    use ResolvesCommission;
    public function mapCarCQFRenewalQuote(CarQuote $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        $quote->loadMissing('payments');

        $car_type_insurance_id = $this->getCarTypeInsuranceId($quote) ?? null;
        $current_insurance_status = match ((int) $car_type_insurance_id) {
            CarTypeOfInsuranceIdEnum::Comprehensive => 'ACTIVE_COMP',
            CarTypeOfInsuranceIdEnum::ThirdPartyOnly => 'ACTIVE_TPL',
            default => $quote->current_insurance_status ?? null,
        };

        $quoteData = [
            'customer_id' => $quote->customer_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => sprintf('%s%s', strtoupper(QuoteTypes::CAR->shortCode()), $quoteUuid),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'dob' => $quote->dob,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch' => null,
            'registration_type' => $quote->registration_type ?? CarRegistrationType::PERSONAL,
            'renewal_batch_id' => null,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $renewalsUploadLeads->renewal_import_code,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->premium,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($quote->payments->first()),
            'previous_advisor_id' => $quote->advisor_id,
            'previous_quote_id' => $quote->id,
            'car_make_id' => $quote->car_make_id,
            'car_model_id' => $quote->car_model_id,
            'year_of_manufacture' => $quote->year_of_manufacture,
            'nationality_id' => $quote->nationality_id,
            'currently_insured_with' => $quote?->plan?->insuranceProvider?->text ?? null,
            'vehicle_category' => $quote->vehicle_category,
            'year_of_first_registration' => $quote->year_of_first_registration,
            'car_type_insurance_id' => $car_type_insurance_id ?? null,
            'current_insurance_status' => $current_insurance_status,
            'vehicle_type_id' => $quote->vehicle_type_id,
            'cylinder' => $quote->cylinder,
            'car_model_detail_id' => $quote->car_model_detail_id,
            'seat_capacity' => $quote->seat_capacity,
            'is_modified' => $quote->is_modified,
            'is_bank_financed' => $quote->is_bank_financed,
            'is_gcc_standard' => $quote->is_gcc_standard,
            'tier_id' => $quote->tier_id,
            'vehicle_use' => $quote->vehicle_use,
            'emirate_of_registration_id' => $quote->emirate_of_registration_id,
            'car_value' => $quote->car_value,
            'uae_license_held_for_id' => $this->getNextUAELicenseHeldForId($quote),
        ];

        $lookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)
            ->first();

        if ($lookup) {
            $quoteData['transaction_type_id'] = $lookup->id;
        }

        return $quoteData;
    }

    public function mapFailedQuoteData(CarQuote $quote): array
    {
        $quote->loadMissing('payments');

        return [
            'customer_name' => $quote->first_name.' '.$quote->last_name ?? null,
            'email' => $quote->email ?? null,
            'mobile_no' => $quote->mobile_no,
            'quote_type' => str_replace('-', '', QuoteTypes::CAR->shortCode()),
            'insurer' => $quote?->plan?->insuranceProvider?->text ?? null,
            'product' => 'Motor insurance',
            'product_type' => null,
            'advisor' => null,
            'policy_number' => $quote->policy_number,
            'start_date' => $quote->policy_start_date ? Carbon::parse($quote->policy_start_date)->format('d/m/Y') : null,
            'end_date' => $quote->policy_expiry_date ? Carbon::parse($quote->policy_expiry_date)->format('d/m/Y') : null,
            'batch' => null,
            'make' => $quote->carMake?->text ?? null,
            'model' => $quote->carModel?->text ?? null,
            'year' => $quote->year_of_manufacture ?? null,
            'previous_advisor' => $quote->advisor?->email ?? null,
            'previous_quote_policy_premium' => $quote->premium ?? null,
            'previous_quote_policy_commission' => $this->resolveTotalCommission($quote->payments->first()),
            'previous_ref_id' => $quote->code ?? null,
            'source' => $quote->source ?? null,
            'notes' => $quote->additional_notes ?? null,
            'plan_name' => null,
            'errors' => $quote->validation_errors ?? null,
        ];
    }

    public function getCarTypeInsuranceId(CarQuote|PersonalQuote $quote): ?int
    {
        $plan = $this->resolveQuotePlan($quote);

        // Collect possible fields to check for insurance type
        $fields = [
            $plan?->insurance_type ?? '',
            $plan?->text ?? '',
        ];

        // Normalize fields for case-insensitive comparison
        $fields = array_filter(array_map('strtolower', $fields));

        // Check for partial match against enum values
        // Maps enum values to their car_type_insurance codes (TPL is stored as "Third Party Only")
        $typeInsuranceCodes = [
            carTypeInsuranceCode::Comprehensive => carTypeInsuranceCode::Comprehensive,
            carTypeInsuranceCode::ThirdPartyOnly => carTypeInsuranceCode::ThirdPartyOnly,
            carTypeInsuranceCode::TPL => carTypeInsuranceCode::ThirdPartyOnly,
        ];

        foreach ($typeInsuranceCodes as $enumCase => $dbCode) {
            $enumValue = strtolower($enumCase);

            foreach ($fields as $field) {
                if (str_contains($field, $enumValue)) {
                    return app(RenewalsAddonServices::class)->getCarTypeOfInsurance($dbCode)->id ?? null;
                }
            }
        }

        // Fallback: return the first non-empty original value
        foreach ($fields as $idx => $field) {
            $original = $plan?->insurance_type ?? $plan?->text;
            if (! empty($original)) {
                return app(RenewalsAddonServices::class)->getCarTypeOfInsurance($original)->id ?? null;
            }
        }

        return null;
    }

    /**
     * Resolve the related CarPlan without touching CarQuote's column-named relationship
     * methods (e.g. plan_id()), which recurse infinitely when the model is only partially
     * hydrated and the plan_id attribute is not loaded.
     */
    private function resolveQuotePlan(CarQuote|PersonalQuote $quote): ?CarPlan
    {
        if ($quote instanceof PersonalQuote) {
            return $quote->carPlan;
        }

        if ($quote->relationLoaded('plan')) {
            return $quote->getRelation('plan');
        }

        $planId = $quote->getRawOriginal('plan_id');

        return $planId !== null ? CarPlan::find($planId) : null;
    }

    public function getNextUAELicenseHeldForId(CarQuote $quote)
    {
        $currentId = (int) $quote->uae_license_held_for_id;
        $maxEnumValue = UAELicenseHeldForEnum::FIVE_YEARS->value;

        // If current is null or not a valid enum, return null
        $currentEnum = UAELicenseHeldForEnum::fromId($currentId);
        if (! $currentEnum) {
            return null;
        }

        // If already at max, return max
        if ($currentId >= $maxEnumValue) {
            return $maxEnumValue;
        }

        // Otherwise, increment and return, capped at max
        $nextId = $currentId + 1;
        if ($nextId > $maxEnumValue) {
            $nextId = $maxEnumValue;
        }

        return UAELicenseHeldForEnum::fromId($nextId)?->value ?? null;
    }
}
