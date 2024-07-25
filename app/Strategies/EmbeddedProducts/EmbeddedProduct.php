<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\EmbeddedProductEnum;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class EmbeddedProduct
{
    use GenericQueriesAllLobs;

    public function getPDFData($quoteObject, $certificate_number, $premium)
    {
        throw new Exception('Method not implemented');
    }

    /**
     * Retrieves sold transaction data from a dataset.
     *
     * @return Collection
     */
    public function getTransactionData($dataset, $isAlfredProtect = false)
    {
        $dataset->each(function ($item) use($isAlfredProtect) {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $quoteObject = $item->quoteRequest;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $carMake = $quoteObject->carMake->text ?? '';
            $carModel = $quoteObject->carModel->text ?? '';
            $advisorName = $quoteObject->advisor->name ?? '';
            $age = isset($quoteObject->dob) ?
                Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now()).' Years'
                : '';
            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
            } else {
                $firstName = $customer->insured_first_name ?? '';
                $lastName = $customer->insured_last_name ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->advisor_name = $advisorName;
            $item->payment_date = isset($item->paid_at) ? Carbon::parse($item->paid_at)->format($dateFormat) : '';
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $firstName.' '.$lastName;
            $item->dob = isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '';
            $item->age = $age;
            $item->vehicle = $carMake.' '.$carModel;
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->email = $quoteObject->email ?? '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            $item->policy_issuance_date = $quoteObject->policy_issuance_date ?? '';
            $item->emirates_id_number = $customer->emirates_id_number ?? '';

            if($isAlfredProtect) {
                $item->plan_type = EmbeddedProductEnum::{$item->product->embeddedProduct->short_code}()->value;
                $item->tax_invoice_no = $item->tax_invoice_no ?? '';
                $item->tax_invoice_buyer_no = $item->tax_invoice_buyer_no ?? '';
                $item->credit_note_no = $item->credit_note_no ?? '';
                $item->credit_note_buyer_no = $item->credit_note_buyer_no ?? '';
                $item->commission_with_vat = $item->commission_without_vat ?? '';
                $item->policy_price = $item->policy_price ?? '';
                $item->policy_status = $item->policy_status ?? '';
            }


            return $item;
        });

        return $dataset;
    }
}
