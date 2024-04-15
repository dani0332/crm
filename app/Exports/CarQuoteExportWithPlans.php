<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use App\Traits\ExcelExportable;
use Carbon\Carbon;

class CarQuoteExportWithPlans
{
    use ExcelExportable;

    public function collection()
    {
        return app(CarQuoteService::class)->getExportDataWithPlans();
    }

    public function headings(): array
    {
        return [
            'Ref ID',
            'First Name',
            'Last Name',
            'Date of Birth',
            'Age',
            'Nationality',
            'Car Make',
            'Car Model',
            'Car Model Year',
            'Car Value',
            'Car Value (At Enquiry)',
            'Vehicle Type',
            'Emirate Of Registration',
            'Repair Type',
            'Created Date',
            'Paid At',
            'Lead Status',
            'Payment Status',
            'Plan Name',
            'Provider Name',
            'Addon Name',
            'Addon Value',
            'IsSelected',

        ];
    }

    public function map($quote): array
    {

        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            date('d-m-Y', strtotime($quote->dob)),
            Carbon::parse($quote->dob)->age,
            $quote->nationality,
            $quote->car_make,
            $quote->car_model,
            $quote->year_of_manufacture,
            $quote->car_value,
            $quote->car_value_tier,
            $quote->vehicle_type,
            $quote->emirate_of_registration,
            $quote->repair_type,
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->paid_at)),
            $quote->lead_status,
            $quote->payment_status,
            $quote->plan_name,
            $quote->provider_name,
            $quote->add_on_name,
            $quote->add_on_value,
            $quote->is_selected,
        ];
    }
}
