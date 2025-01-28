<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\QuoteDocumentsEnum;
use App\Services\SukoonDemocranceService;
use finfo;
use Illuminate\Support\Facades\Storage;

class AlfredProtect extends EmbeddedProduct
{
    public function syncSukoonDemocrance($quoteObject, $transaction)
    {
        $sukoonDemocrance = new SukoonDemocranceService;
        $sukoonDemocrance->processDemocranceSubmission($quoteObject, $transaction);
    }

    /**
     * Retrieves the Certificate document for a quote object.
     *
     * @param  object  $quoteObject
     * @param  string  $certificateNumber
     * @param  float  $premium
     * @return array
     */
    public function getCertificateDocument($ep, $transaction, $quoteObject)
    {
        $documents = $transaction->documents()->get();
        if (isset($documents)) {
            $docs = $documents->map(function ($document) {
                $file = Storage::disk('azureIM')->get($document->doc_url);
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->buffer($file);
                $filePath = Storage::disk('azureIM')->url($document->doc_url);

                return [
                    'name' => $document->doc_name,
                    'path' => $document->doc_url,
                ];
            });

            return $docs;
        } else {
            return [];
        }
    }

    /**
     * Retrieves the Certificate url for a quote object.
     *
     * @param  object  $quoteObject
     * @param  string  $certificateNumber
     * @param  float  $premium
     * @return array
     */
    public function getCertificateDocumentUrl($ep, $transaction, $quoteObject)
    {
        $documents = $transaction->documents()->count();
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');
        if ($documents > 0) {
            $document = $transaction->documents->where('document_type_code', QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE)->first();
            $url = $azureStorageUrl.$azureStorageContainer.'/'.$document->doc_url;

            return $url;

        }
    }

    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'ADVISOR NAME',
            'DATE OF ISSUANCE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'Plan Type',
            'CERTIFICATE NUMBER',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'LOB',
            'CONTRIBUTION AMOUNT',
            'LEAD STATUS',
            'Tax Invoice no.',
            'Premium with VAT / GPW (AED)',
            'Tax Invoice Buyer no.',
            'Commission with VAT(AED)',
            'Credit Note No.',
            'Premium with VAT / GPW (AED)',
            'Credit Note Buyer no.',
            'Commission with VAT(AED)',
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
            $certificate->plan_type,
            $certificate->certificate_number,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->age,
            $certificate->lob,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->tax_invoice_no,
            $certificate->premium_with_vat,
            $certificate->tax_invoice_buyer_no,
            '',
            $certificate->credit_note_no,
            '',
            $certificate->credit_note_buyer_no,
            '',
        ];
    }
}
