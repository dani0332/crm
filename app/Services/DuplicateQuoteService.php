<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use Carbon\Carbon;

class DuplicateQuoteService
{
    public function prepareLifeQuoteDuplicateData(PersonalQuote $parentRecord): array
    {
        $lifeDataArr = [
            'first_name' => $parentRecord->first_name,
            'last_name' => $parentRecord->last_name,
            'email' => $parentRecord->email,
            'mobile_no' => $parentRecord->mobile_no,
        ];

        if ($parentRecord->quote_type_id == QuoteTypeId::Life && $parentRecord->lifeQuote) {
            $lifeQuote = $parentRecord->lifeQuote;
            $lifeDataArr['dob'] = $lifeQuote->dob ?? $parentRecord->dob;
            $lifeDataArr['sum_insured_value'] = $lifeQuote->sum_insured_value ?? null;
            $lifeDataArr['nationality_id'] = $lifeQuote->nationality_id ?? $parentRecord->nationality_id ?? null;
            $lifeDataArr['sum_insured_currency_id'] = $lifeQuote->sum_insured_currency_id ?? null;
            $lifeDataArr['marital_status_id'] = $lifeQuote->marital_status_id ?? null;
            $lifeDataArr['purpose_of_insurance_id'] = $lifeQuote->purpose_of_insurance_id ?? null;
            $lifeDataArr['number_of_years_id'] = $lifeQuote->number_of_years_id ?? null;
            $lifeDataArr['is_smoker'] = $lifeQuote->is_smoker ?? 0;
            $lifeDataArr['gender'] = $lifeQuote->gender ?? $parentRecord->gender ?? null;
            $lifeDataArr['others_info'] = $lifeQuote->others_info ?? null;
            $lifeDataArr['height'] = $lifeQuote->height ?? null;
            $lifeDataArr['weight'] = $lifeQuote->weight ?? null;
            $lifeDataArr['bmi'] = $lifeQuote->bmi ?? null;
            $lifeDataArr['age'] = $lifeQuote->age ?? ($lifeDataArr['dob'] ? Carbon::parse($lifeDataArr['dob'])->age : null);
        } else {
            $lifeDataArr['dob'] = $parentRecord->dob ?? null;
            $lifeDataArr['sum_insured_value'] = null;
            $lifeDataArr['nationality_id'] = $parentRecord->nationality_id ?? null;
            $lifeDataArr['sum_insured_currency_id'] = null;
            $lifeDataArr['marital_status_id'] = null;
            $lifeDataArr['purpose_of_insurance_id'] = null;
            $lifeDataArr['number_of_years_id'] = null;
            $lifeDataArr['is_smoker'] = 0;
            $lifeDataArr['gender'] = $parentRecord->gender ?? null;
            $lifeDataArr['others_info'] = null;
            $lifeDataArr['height'] = null;
            $lifeDataArr['weight'] = null;
            $lifeDataArr['bmi'] = null;
            $lifeDataArr['age'] = $parentRecord->dob ? Carbon::parse($parentRecord->dob)->age : null;
        }

        return $lifeDataArr;
    }

    public function prepareHomeQuoteDuplicateData(PersonalQuote $parentRecord): array
    {
        $homeDataArr = [
            'first_name' => $parentRecord->first_name,
            'last_name' => $parentRecord->last_name,
            'email' => $parentRecord->email,
            'mobile_no' => $parentRecord->mobile_no,
        ];

        if ($parentRecord->quote_type_id == QuoteTypeId::Home && $parentRecord->homeQuote) {
            $homeQuote = $parentRecord->homeQuote;
            $homeDataArr['has_contents'] = $homeQuote->has_contents ?? 0;
            $homeDataArr['has_building'] = $homeQuote->has_building ?? 0;
            $homeDataArr['have_claimed_losses'] = $homeQuote->have_claimed_losses ?? 0;
            $homeDataArr['building_aed'] = $homeQuote->building_aed ?? null;
            $homeDataArr['has_personal_belongings'] = $homeQuote->has_personal_belongings ?? 0;
            $homeDataArr['owner_occupancy_type_id'] = $homeQuote->owner_occupancy_type_id ?? null;
            $homeDataArr['ilivein_accommodation_type_id'] = $homeQuote->ilivein_accommodation_type_id ?? null;
            $homeDataArr['iam_possesion_type_id'] = $homeQuote->iam_possesion_type_id ?? null;
            $homeDataArr['sub_area_id'] = $homeQuote->sub_area_id ?? null;
            $homeDataArr['contents_aed'] = $homeQuote->contents_aed ?? null;
            $homeDataArr['personal_belongings_aed'] = $homeQuote->personal_belongings_aed ?? null;
            $homeDataArr['type_of_coverage_you_need'] = $homeQuote->type_of_coverage_you_need ?? null;
            $homeDataArr['address'] = $homeQuote->address ?? $parentRecord->address ?? null;
            $homeDataArr['dob'] = $homeQuote->dob ?? $parentRecord->dob ?? null;
            $homeDataArr['nationality_id'] = $homeQuote->nationality_id ?? $parentRecord->nationality_id ?? null;
            $homeDataArr['gender'] = $homeQuote->gender ?? $parentRecord->gender ?? null;
            $homeDataArr['company_name'] = $homeQuote->company_name ?? null;
            $homeDataArr['company_address'] = $homeQuote->company_address ?? null;
        } else {
            $homeDataArr['has_contents'] = 0;
            $homeDataArr['has_building'] = 0;
            $homeDataArr['have_claimed_losses'] = 0;
            $homeDataArr['building_aed'] = null;
            $homeDataArr['has_personal_belongings'] = 0;
            $homeDataArr['owner_occupancy_type_id'] = null;
            $homeDataArr['ilivein_accommodation_type_id'] = null;
            $homeDataArr['iam_possesion_type_id'] = null;
            $homeDataArr['sub_area_id'] = null;
            $homeDataArr['contents_aed'] = null;
            $homeDataArr['personal_belongings_aed'] = null;
            $homeDataArr['type_of_coverage_you_need'] = null;
            $homeDataArr['address'] = $parentRecord->address ?? null;
            $homeDataArr['dob'] = $parentRecord->dob ?? null;
            $homeDataArr['nationality_id'] = $parentRecord->nationality_id ?? null;
            $homeDataArr['gender'] = $parentRecord->gender ?? null;
            $homeDataArr['company_name'] = null;
            $homeDataArr['company_address'] = null;
        }

        return $homeDataArr;
    }
}
