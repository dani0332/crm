<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use App\Traits\ExcelExportable;
use Illuminate\Support\Facades\DB;

class CarQuoteExport
{
    use ExcelExportable;

    public function collection()
    {
        return app(CarQuoteService::class)->getGridData()->select(
            'cqr.code',
            'qb.name as quote_batch_id_text',
            'cqr.first_name',
            'cqr.last_name',
            DB::raw('DATE_FORMAT(cqr.dob, "%d-%m-%Y") as dob'),
            'cqr.source',
            'n.TEXT AS nationality_id_text',
            'ulhf.TEXT AS uae_license_held_for_id_text',
            'cmake.TEXT AS car_make_id_text',
            'cmodel.TEXT AS car_model_id_text',
            'cqr.year_of_manufacture',
            'cqr.year_of_first_registration',
            'cqr.car_value',
            'cqr.car_value_tier',
            'vt.text as vehicle_type_id_text',
            'cqr.current_insurance_status',
            'cqr.currently_insured_with as currently_insured_with_text',
            'ch.TEXT AS claim_history_id_text',
            DB::raw('DATE_FORMAT(cqr.created_at, "%d-%m-%Y %H:%i:%s") as created_at'),
            DB::raw('DATE_FORMAT(cqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
            't.cost_per_lead as cost_per_lead',
            'qs.text AS quote_status_id_text',
            'ps.text AS payment_status_id_text',
            'cqr.is_ecommerce',
            't.name as tier_id_text',
            'qvc.visit_count as visit_count',
            DB::raw('DATE_FORMAT(cqrd.next_followup_date, "%d-%m-%Y %H:%i:%s") as next_followup_date'),
            'cqr.updated_at as updated_at',
            'cqr.updated_by',
            'cqr.additional_notes',
            'u.name AS advisor_id_text',
            'cqr.policy_number',
            DB::raw('DATE_FORMAT(cqr.policy_expiry_date, "%d-%m-%Y") as policy_expiry_date'),
            'cqr.is_gcc_standard',
            'cqr.is_modified',
            'cqr.premium',
            'ls.text as lost_reason',
            'cqr.quote_link')->get();
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
            'RENEWAL EXPIRY DATE',
            'IS GCC STANDARD',
            'IS VEHICLE MODIFIED',
            'PREMIUM',
            'LOST REASON',
            'QUOTE LINK',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->quote_batch_id_text,
            $quote->first_name,
            $quote->last_name,
            date('m/d/Y', strtotime($quote->dob)),
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
            date('m/d/Y H:i:s', strtotime($quote->created_at)),
            $quote->advisor_assigned_date ? date('m/d/Y H:i:s', strtotime($quote->advisor_assigned_date)) : 'N/A',
            $quote->cost_per_lead,
            $quote->quote_status_id_text,
            $quote->payment_status_id_text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->tier_id_text,
            $quote->visit_count,
            date('m/d/Y H:i:s', strtotime($quote->next_followup_date)),
            date('m/d/Y H:i:s', strtotime($quote->updated_at)),
            $quote->updated_by,
            $quote->additional_notes,
            $quote->advisor_id_text,
            $quote->policy_number,
            date('m/d/Y H:i:s', strtotime($quote->policy_expiry_date)),
            $quote->is_gcc_standard ? 'Yes' : 'No',
            $quote->is_modified ? 'Yes' : 'No',
            $quote->premium,
            $quote->lost_reason,
            $quote->quote_link,
        ];
    }

}
