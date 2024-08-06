<?php

namespace App\Strategies\EmbeddedProducts;

class TravelAnnual extends EmbeddedProduct
{
    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'ADVISOR NAME',
            'PAYMENT DATE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'CERTIFICATE NUMBER',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'PASSPORT NUMBER',
            'NATIONALITY',
            'AGE',
            'VEHICLE',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->advisor_name,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->certificate_number,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->passport_number,
            $certificate->nationality,
            $certificate->age,
            $certificate->vehicle,
            $certificate->contribution_amount,
            $certificate->status,
        ];
    }
}
