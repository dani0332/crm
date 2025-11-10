<?php

namespace App\Strategies\EmbeddedProducts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ECB extends EmbeddedProduct
{
    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'DATE OF ISSUANCE', // Payment Date
            'PLAN COMMENCEMENT DATE', // Plan Start Date
            'PLAN END DATE',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'Contribution Amount', // Payment Amount With VAT
            'POLICY ISSUE STATUS',
            'Certificate Number',
            'Model Year',
            'Make',
            'Model',
            'Chassis Number',
            'Excess Amount',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->certificate_number,
            $certificate->model_year,
            $certificate->make,
            $certificate->model,
            $certificate->chassis_number,
            $certificate->excess_amount,
        ];
    }

    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.carMake',
            'quoteRequest.carModel',
            'quoteRequest.customer',
            'quoteRequest.customer.customerInsured',
            'quoteRequest.customer.customerInsured.insured',
            'quoteRequest.quoteStatus',
            'quoteRequest.quoteRequestEntityMapping',
        ];
    }

    public function updateQuery($query, $filters)
    {
        return $query->join('car_quote_request_detail', function ($join) {
            $join->on('embedded_transactions.quote_request_id', '=', 'car_quote_request_detail.car_quote_request_id')
                ->where('embedded_transactions.quote_request_type', '=', 'App\\Models\\CarQuote');
        })
            ->when(isset($filters['chassis_number']), function ($query) use ($filters) {
                $query->where('car_quote_request_detail.chassis_number', 'like', "%{$filters['chassis_number']}%");
            });
    }

    public function processReportRecord($quoteObject, $item)
    {
        $item->model_year = $quoteObject?->year_of_manufacture ?? '';
        $item->make = $quoteObject?->carMake?->text ?? '';
        $item->model = $quoteObject?->carModel?->text ?? '';
        $item->chassis_number = $quoteObject?->carQuoteRequestDetail?->chassis_number ?? '';
        $item->excess_amount = $quoteObject?->carQuoteRequestDetail?->excess.'/-';

        return $item;
    }
}
