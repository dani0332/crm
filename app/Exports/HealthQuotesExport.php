<?php

namespace App\Exports;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use App\Traits\ExcelExportable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        return app(HealthQuoteService::class)->getGridData()->select(
            'hqr.code',
            'hqr.first_name',
            'hqr.last_name',
            'qs.text as quote_status_id_text',
            'u.name as advisor_id_text',
            'wcu.name as wcu_id_text',
            'hqr.created_at',
            'hqr.updated_at',
            'hqr.health_team_type',
            'hqrd.transapp_code',
            'ls.text as lost_reason',
            'hqr.price_starting_from',
            'hqr.premium',
            'hqr.policy_number',
            'hqr.source',
            'lt.TEXT AS lead_type_id_text',
            'sb.text as salary_band_id_text',
            'mc.text as member_category_id_text',
            'ins_provider.TEXT as currently_insured_with_id_text',
            'hqr.is_ecommerce',
            'hqr.device',
            'hqr.gender',
            'n.TEXT AS nationality_id_text',
            'hqr.dob',
            'e.TEXT AS emirate_of_your_visa_id_text',
            DB::raw('IF(EXISTS (
                SELECT *
                FROM quote_request_entity_mapping
                WHERE quote_type_id = '.QuoteTypeId::Health.' AND quote_request_id = hqr.id),
                "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
            as customer_type'),
            'hp.text as health_plan_name_text',
            'ihp.text as plan_provider_name_text',
            'hqr.renewal_batch',
        )->get();
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
            'RENEWAL BATCH',
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
            $quote->renewal_batch,
        ];
    }
}
