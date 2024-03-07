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
        $data = [
            'name' => $quoteObject->first_name.' '.$quoteObject->last_name,
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
        $data['contribution_amount'] = $data['plan_currency']." {$premium}  (Including VAT) Per Annum";

        return $data;
    }
}
