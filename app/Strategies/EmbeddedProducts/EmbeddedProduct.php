<?php

namespace App\Strategies\EmbeddedProducts;

use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;

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
     * @return array
     */
    public function getTransactionData($dataset)
    {
        return $dataset->map(function ($item) {

            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $modelType = $item->model_type;
            $quoteId = $item->quote_request_id;
            $quoteObject = $this->getQuoteObject($modelType, $quoteId);
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

            return [
                'id' => $item->id,
                'ref_id' => $item->code,
                'payment_date' => isset($item->paid_at) ? Carbon::parse($item->paid_at)->format($dateFormat) : '',
                'plan_start_date' => $planStartDate,
                'plan_end_date' => $planEndDate,
                'certificate_number' => $item->certificate_number ?? '',
                'name' => $quoteObject->first_name.' '.$quoteObject->last_name,
                'dob' => isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '',
                'age' => $age,
                'vehicle' => $carMake.' '.$carModel,
                'contact_number' => $quoteObject->mobile_no ?? '',
                'email' => $quoteObject->email ?? '',
                'contribution_amount' => 'AED '.$item->price_with_vat.'/-',
                'status' => $status,
                'policy_issuance_date' => $quoteObject->policy_issuance_date ?? '',
                'emirates_id_number' => $customer->emirates_id_number ?? '',
            ];
        });
    }
}
