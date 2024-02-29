<?php

namespace App\Strategies;

use Carbon\Carbon;

class MDX extends EmbeddedProduct
{
    /**
     * Retrieves the PDF data for a quote object.
     *
     * @param object $quoteObject
     * @param string $certificateNumber
     * @param float $premium
     * @return array
     */
    public function getPDFData($quoteObject, $certificateNumber, $premium)
    {
        $data = [
            'name' => $quoteObject->first_name . ' ' . $quoteObject->last_name,
            'dob' => isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format('m/d/Y') : '',
            'emirates_id' => $quoteObject->customer->emirates_id_number ?? '',
            'plan_type' => 'Individual',
            'certificate_number' => $certificateNumber, // plan no
            'plan_currency' => 'AED',
            'plan_term' => '1 Year effect from Plan Commencement date and Subject to Contribution Paid',
            'date_of_enrollment' => isset($quoteObject->policy_start_date) ? Carbon::parse($quoteObject->policy_start_date)->format('m/d/Y') : '', // Plan Commencement Date
            'plan_beneficiary' => 'As per Shari’ah',
            'policy_insurance_date' => isset($quoteObject->policy_issuance_date) ? Carbon::parse($quoteObject->policy_issuance_date)->format('m/d/Y') : '',
        ];
        $data['contribution_amount'] = $data['plan_currency'] . " {$premium}  (Including VAT) Per Annum";

        return $data;
    }

    /**
     * Retrieves sold transaction data from a dataset.
     *
     * @param $dataset
     * @return array
     */
    public function getTransactionData($dataset)
    {
        return $dataset->map(function ($item) {
            $modelType = $item->model_type;
            $quoteId = $item->quote_request_id;
            $quoteObject = $this->getQuoteObject($modelType, $quoteId);
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer;
            $carMake = $quoteObject->carMake->text ?? '';
            $carModel = $quoteObject->carModel->text ?? '';
            $age = isset($quoteObject->dob) ?
                Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now()) . ' Years'
                : '';
            $planStartDate = isset($quoteObject->policy_start_date) ? Carbon::parse($quoteObject->policy_start_date)->format('m/d/Y') : '';
            $planEndDate = '';
            if (isset($planEndDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format('m/d/Y');
            }

            return [
                'id' => $item->id,
                'ref_id' => $item->code,
                'payment_date' => isset($item->paid_at) ? Carbon::parse($item->paid_at)->format('m/d/Y') : '',
                'plan_start_date' => $planStartDate,
                'plan_end_date' => $planEndDate,
                'certificate_number' => $item->certificate_number ?? '',
                'name' => $quoteObject->first_name . ' ' . $quoteObject->last_name,
                'dob' => isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format('m/d/Y') : '',
                'age' => $age,
                'vehicle' => $carMake . ' ' . $carModel,
                'contact_number' => $quoteObject->mobile_no ?? '',
                'email' => $quoteObject->email ?? '',
                'contribution_amount' => 'AED ' . $item->price_with_vat . '/-',
                'status' => $status,
                'policy_issuance_date' => $quoteObject->policy_issuance_date ?? '',
                'emirates_id_number' => $customer->emirates_id_number ?? '',
            ];
        });
    }
}
