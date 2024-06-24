<?php

namespace App\Exports;

use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use App\Traits\ExcelExportable;
use Carbon\Carbon;

class HealthQuotesExport
{
    use ExcelExportable;

    private $genderOptions;

    public function __construct()
    {
        $this->genderOptions = app(CRUDService::class)->getGenderOptions();
    }

    public function collection()
    {

        return app(HealthQuoteService::class)->forExportGridData()->get();
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'WC ADVISOR',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'HEALTH TEAM TYPE',
            'TRANSAPP CODE',
            'LOST REASON',
            'STARTING FROM',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LEAD TYPE',
            'SALARY BAND',
            'MEMBER CATEGORY',
            'CURRENTLY INSURED WITH',
            'IS ECOMMERCE',
            'Device',
            'Gender',
            'Nationality',
            'Age Bands',
            'Emirates of Visa',
            'FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?',
            'TYPE OF PLAN',
            'Provider Name',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->quote_status_id_text,
            $quote->advisor_id_text,
            $quote->wcu_id_text,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->health_team_type,
            $quote->transapp_code,
            $quote->lost_reason,
            $quote->price_starting_from,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->lead_type_id_text,
            $quote->salary_band_id_text,
            $quote->member_category_id_text,
            $quote->currently_insured_with_id_text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->device,
            $this->genderOptions[$quote->gender] ?? '',
            $quote->nationality_id_text,
            Carbon::parse($quote->dob)->age,
            $quote->emirate_of_your_visa_id_text,
            $quote->customer_type,
            $quote->health_plan_name_text,
            $quote->plan_provider_name_text,
        ];
    }
}
