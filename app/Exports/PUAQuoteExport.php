<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use App\Traits\ExcelExportable;
use Illuminate\Support\Facades\DB;

class PUAQuoteExport
{
    use ExcelExportable;

    public function collection()
    {
        return $update = DB::select(DB::raw("
    SELECT
        q.code AS 'Ref-ID',
        q.premium_authorized AS 'Premium Authorized',
        q.payment_status_date AS 'Payment Auth Date',
        qs.text AS 'Lead Status',
        'AUTHORIZED' AS 'Payment Status',
        q.source AS 'Source',
        cmk.text AS 'Make',
        cmd.text AS 'Model',
        u.email AS 'Assigned Advisor Email'
    FROM
        car_quote_request q
    LEFT JOIN car_make cmk ON q.car_make_id = cmk.id
    LEFT JOIN car_model cmd ON q.car_model_id = cmd.id
    LEFT JOIN users u ON q.advisor_id = u.id
    INNER JOIN user_team ut ON q.advisor_id = ut.user_id
    INNER JOIN teams t ON ut.team_id = t.id
    INNER JOIN quote_status qs ON q.quote_status_id = qs.id
    WHERE
        q.payment_status_id = 4
        AND q.quote_status_id NOT IN (71, 33)
        AND q.paid_at <= DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR
        AND q.paid_at > DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY
        AND t.parent_team_id = 3
        AND q.uuid NOT IN (
            SELECT
                q.uuid
            FROM
                car_quote_plan_details cqp
            JOIN car_quote_request q ON cqp.quote_uuid = q.uuid
            LEFT JOIN car_plan cp ON q.plan_id = cp.id
            LEFT JOIN insurance_provider ip ON cp.provider_id = ip.id
            WHERE
                q.payment_status_id = 4
                AND q.quote_status_id NOT IN (71, 33)
                AND cqp.pua_premium IS NOT NULL
                AND q.paid_at <= DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR
                AND q.paid_at > DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY
                AND cqp.plan_id = q.plan_id
        )
    ORDER BY q.paid_at DESC
"));

    }

    public function headings(): array
    {
        return [
            'CDB ID',
            'BATCH',
            'FIRST NAME',
            'LAST NAME',
            'DATE OF BIRTH',
            'LEAD SOURCE',
            'NATIONALITY',
            'UAE LICENCE HELD FOR',
            'CAR MAKE',
            'CAR MODEL',
            'CAR MODEL YEAR',
            'FIRST REGISTRATION DATE',
            'CAR VALUE',
            'CAR VALUE (AT ENQUIRY)',
            'VEHICLE TYPE',
            'TYPE OF CAR INSURANCE',
            'CURRENTLY INSURED WITH',
            'CLAIM HISTORY',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LEAD COST',
            'LEAD STATUS',
            'PAYMENT STATUS',
            'ECOMMERCE',
            'TIER NAME',
            'VISIT COUNT',
            'FOLLOW UP DATE',
            'LAST MODIFIED DATE',
            'UPDATED BY',
            'ADDITIONAL NOTES',
            'ADVISOR',
            'POLICY NUMBER',
            'POLICY EXPIRY DATE',
            'IS GCC STANDARD',
            'IS VEHICLE MODIFIED',
            'PREMIUM',
            'LOST REASON',
            'QUOTE LINK',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->quote_batch_id_text,
            $quote->first_name,
            $quote->last_name,
            $quote->dob ? date(config('constants.datetime_format'), strtotime($quote->dob)) : '',
            $quote->source,
            $quote->nationality_id_text,
            $quote->uae_license_held_for_id_text,
            $quote->car_make_id_text,
            $quote->car_model_id_text,
            $quote->year_of_manufacture,
            $quote->year_of_first_registration,
            $quote->car_value,
            $quote->car_value_tier,
            $quote->vehicle_type_id_text,
            $quote->current_insurance_status,
            $quote->currently_insured_with_text,
            $quote->claim_history_id_text,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            $quote->advisor_assigned_date ? date(config('constants.datetime_format'), strtotime($quote->advisor_assigned_date)) : '',
            $quote->cost_per_lead,
            $quote->quote_status_id_text,
            $quote->payment_status_id_text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->tier_id_text,
            $quote->visit_count,
            $quote->next_followup_date ? date(config('constants.datetime_format'), strtotime($quote->next_followup_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->updated_by,
            $quote->additional_notes,
            $quote->advisor_id_text,
            $quote->policy_number,
            $quote->policy_expiry_date ? date(config('constants.datetime_format'), strtotime($quote->policy_expiry_date)) : '',
            $quote->is_gcc_standard ? 'Yes' : 'No',
            $quote->is_modified ? 'Yes' : 'No',
            $quote->premium,
            $quote->lost_reason,
            $quote->quote_link,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
        ];
    }

}
