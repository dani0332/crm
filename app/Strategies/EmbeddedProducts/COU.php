<?php

namespace App\Strategies\EmbeddedProducts;

class COU extends EmbeddedProduct
{
    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.customer',
            'quoteRequest.customer.nationality',
            'quoteRequest.latestInsured',
            'quoteRequest.quoteStatus',
            'quoteRequest.advisor',
            'quoteRequest.quoteRequestEntityMapping',
            'paymentStatus',
        ];
    }

    protected function postFilterReportProcessing($dataset)
    {
        return $this->loadVehicleRelations($dataset);
    }

    protected function processReportRecord($quoteObject, $item)
    {
        return $this->processCarBikeReportRecord($quoteObject, $item);
    }

    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'Line of Business',
            'ADVISOR NAME',
            'DATE OF ISSUANCE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'VEHICLE',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
            'EP Payment Status',
            'CERTIFICATE NUMBER',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->lob ?? '',
            $certificate->advisor_name,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->age,
            $certificate->vehicle,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->ep_payment_status,
            $certificate->certificate_number,
        ];
    }
}
