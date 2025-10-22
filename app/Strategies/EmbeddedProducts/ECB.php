<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\PaymentStatusEnum;
use App\Models\EmbeddedTransaction;
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
            'Contact Number',
            'Email ID',
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
            $certificate->contact_number,
            $certificate->email,
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
        $item->excess_amount = $quoteObject?->carQuoteRequestDetail?->excess . '/-';

        return $item;
    }

    /**
     * Retrieves sold transaction data from a dataset.
     *
     * @return Collection
     */
    public function getTransactionData1($dataset, $isAlfredProtect = false)
    {
        $dataset->each(function ($item) {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $quoteObject = $item->quoteRequest;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $customerInsured = $customer?->customerInsured()
                ->where('quote_request_id', $item->quote_request_id)
                ->where('quote_type_id', $item->quote_type_id)
                ->latest('updated_at')
                ->first() ?? null;

            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
            } else {
                $firstName = ($customerInsured?->insured?->first_name ?? $customer?->insured_first_name) ?? '';
                $lastName = ($customerInsured?->insured?->last_name ?? $customer?->insured_last_name) ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->quote_request = $item->quoteRequest;
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $firstName.' '.$lastName;
            $item->email = $quoteObject->email ?? '';
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->emirates_id_number = ($customerInsured?->insured?->id_number ?? $customer?->emirates_id_number) ?? '';
            $item->model_year = $quoteObject->year_of_manufacture ?? '';
            $item->make = $quoteObject->carMake?->text ?? '';
            $item->model = $quoteObject->carModel?->text ?? '';
            $item->chassis_number = $quoteObject->carQuoteRequestDetail?->chassis_number ?? '';
            $item->excess_amount = $quoteObject->carQuoteRequestDetail?->excess.'/-';
            $item->payment_date = isset($item->captured_at) ? Carbon::parse($item->captured_at)->format($dateFormat) : '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;

            return $item;
        });

        return $dataset;
    }
}
