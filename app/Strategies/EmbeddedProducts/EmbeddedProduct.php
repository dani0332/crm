<?php

namespace App\Strategies\EmbeddedProducts;

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
    public function getTransactionData($dataset)
    {
        $dataset->each(function ($item) {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $quoteObject = $item->quote_request;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer;
            $carMake = $quoteObject->carMake->text ?? '';
            $carModel = $quoteObject->carModel->text ?? '';
            $age = isset($quoteObject->dob) ?
                Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now()).' Years'
                : '';
            $planStartDate = isset($quoteObject->policy_start_date) ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (isset($planEndDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->payment_date = isset($item->paid_at) ? Carbon::parse($item->paid_at)->format($dateFormat) : '';
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $quoteObject->first_name.' '.$quoteObject->last_name;
            $item->dob = isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '';
            $item->age = $age;
            $item->vehicle = $carMake.' '.$carModel;
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->email = $quoteObject->email ?? '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            $item->policy_issuance_date = $quoteObject->policy_issuance_date ?? '';
            $item->emirates_id_number = $customer->emirates_id_number ?? '';

            return $item;
        });

        return $dataset;
    }
}
