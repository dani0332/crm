<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarQuoteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return app(CarQuoteService::class)->getGridData()->get();
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
            date('d-m-Y H:i:s', strtotime($quote->dob)),
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
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->advisor_assigned_date)),
            $quote->cost_per_lead,
            $quote->quote_status_id_text,
            $quote->payment_status_id_text,
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->tier_id_text,
            $quote->visit_count,
            date('d-m-Y H:i:s', strtotime($quote->next_followup_date)),
            date('d-m-Y H:i:s', strtotime($quote->updated_at)),
            $quote->updated_by,
            $quote->additional_notes,
            $quote->advisor_id_text,
            $quote->policy_number,
            date('d-m-Y H:i:s', strtotime($quote->renewal_expiry_date)),
            $quote->is_gcc_standard ? 'Yes' : 'No',
            $quote->is_modified ? 'Yes' : 'No',
            $quote->premium,
            $quote->lost_reason,
            $quote->quote_link,
        ];
    }

    public function carQuoteExport()
    {
        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());
            $carQuotes = app(CarQuoteService::class)->getExportData()->get();
            foreach ($carQuotes as $quote) {
                fputcsv($handle, $this->map($quote));
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Car-List.csv"',
        ]);
    }
}
