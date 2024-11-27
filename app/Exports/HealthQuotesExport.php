<?php

namespace App\Exports;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TeamNameEnum;
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
            'u.email as advisor_email',
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
            'hqr.previous_policy_expiry_date',
            'hqr.previous_quote_policy_premium',
            'hqr.previous_quote_policy_number',
            'hqr.transaction_approved_at',
            'hqr.policy_booking_date',
            'payment_status.text as payment_status_text',
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT t1.name SEPARATOR ", ")
              FROM teams t1
              WHERE t1.parent_team_id = '.TeamNameEnum::getTeamID(TeamNameEnum::CAR).'
              AND t1.name IN (
                  SELECT t2.name
                  FROM teams t2
                  JOIN user_team ut2 ON t2.id = ut2.team_id
                  WHERE ut2.user_id = u.id)
             ) AS CarTeams')

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
            'ADVISOR EMAIL',
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
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'PAYMENT STATUS',
            'ADVISOR CAR TEAM(s)',
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
            $quote->advisor_email,
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
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            $quote->payment_status_text ?? 'N/A',
            $quote->CarTeams ?? 'N/A',
        ];
    }
}
