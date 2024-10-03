<?php

namespace App\Strategies\EmbeddedProducts;

use Carbon\Carbon;

class MDX extends EmbeddedProduct
{
    /**
     * Retrieves the PDF data for a quote object.
     *
     * @param  object  $quoteObject
     * @param  string  $certificateNumber
     * @param  float  $premium
     * @return array
     */
    public function getPDFData($quoteObject, $certificateNumber, $premium)
    {
        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        if (! empty($quoteObject->quoteRequestEntityMapping)) {
            $firstName = $quoteObject->first_name ?? '';
            $lastName = $quoteObject->last_name ?? '';
        } else {
            $firstName = $quoteObject->customer->insured_first_name ?? '';
            $lastName = $quoteObject->customer->insured_last_name ?? '';
        }

        $data = [
            'name' => $firstName.' '.$lastName,
            'dob' => isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '',
            'emirates_id' => $quoteObject->customer->emirates_id_number ?? '',
            'plan_type' => 'Individual',
            'certificate_number' => $certificateNumber, // plan no
            'plan_currency' => 'AED',
            'plan_term' => '1 Year effect from Plan Commencement date and Subject to Contribution Paid',
            'date_of_enrollment' => isset($quoteObject->policy_start_date) ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '', // Plan Commencement Date
            'plan_beneficiary' => 'As per Shari’ah',
            'policy_insurance_date' => isset($quoteObject->policy_issuance_date) ? Carbon::parse($quoteObject->policy_issuance_date)->format($dateFormat) : '',
        ];
        $data['contribution_amount'] = $data['plan_currency']." {$premium}  (Including VAT) Per Annum";

        return $data;
    }
}
