<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use App\Traits\ExcelExportable;

class CarQuoteExportWithMakeModelTrims
{
    use ExcelExportable;

    public function collection()
    {
        return app(CarQuoteService::class)->getExportDataWithMakeModelTrim();
    }

    public function headings(): array
    {
        return [
            'Make Code',
            'Make Name',
            'Model Code',
            'Model Name',
            'Trim ID',
            'Trim Name',
            'Default Trim ID',
            'Current Value',
            'Axa Car Make',
            'Oman Car Make',
            'Tokio Car Make',
            'Qatar Car Make',
            'Rsa Car Make',
            'Axa Car Model',
            'Oman Car Model',
            'Tokio Car Model',
            'Qatar Car Model',
            'Rsa Car Model',
            'Axa Model Detail',
            'Oman Model Detail',
            'No of Doors',
            'Horsepower',
            'Cubic Capacity',
            'Transmission',
            'Drive Type',
            'Seating Capacity',
            'Cylinder',
            'Body Type',
            'Make Is Active',
            'Model Is Active',
            'Trim Is Active',
            'Make Is Deleted',
            'Model Is Deleted',
            'Trim Is Deleted',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->MakeCode,
            $quote->make_name,
            $quote->code,
            $quote->model_name,
            $quote->trim_id,
            $quote->trim_name,
            $quote->default_trim_id,
            $quote->current_value,
            $quote->axa_car_make,
            $quote->oman_car_make,
            $quote->tokio_car_make,
            $quote->qatar_car_make,
            $quote->rsa_car_make,
            $quote->axa_car_model,
            $quote->oman_car_model,
            $quote->tokio_car_model,
            $quote->qatar_car_model,
            $quote->rsa_car_model,
            $quote->axa_model_detail,
            $quote->oman_model_detail,
            $quote->no_of_doors,
            $quote->hp,
            $quote->cubic_capacity,
            $quote->transmission,
            $quote->drive_type,
            $quote->seating_capacity,
            $quote->cylinder,
            $quote->body_type,
            $quote->make_is_active,
            $quote->mode_is_active,
            $quote->trim_is_active,
            $quote->make_is_deleted,
            $quote->model_is_deleted,
            $quote->trim_is_deleted,
        ];
    }
}
