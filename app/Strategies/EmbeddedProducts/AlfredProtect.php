<?php

namespace App\Strategies\EmbeddedProducts;

use App\Services\SukoonDemocranceService;
use finfo;
use Illuminate\Support\Facades\Storage;

class AlfredProtect extends EmbeddedProduct
{
    public function syncSukoonDemocrance($quoteObject, $transaction)
    {
        $sukoonDemocrance = new SukoonDemocranceService();
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
        $documents = $transaction->documents()->count();
        if ($documents > 0) {
            $document = $transaction->documents()->first();
            $file = Storage::disk('azureIM')->get($document->doc_url);

            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->buffer($file);
            $filePath = Storage::disk('azureIM')->url($document->doc_url);

            return [
                'Name' => $document->doc_name,
                'Path' => $document->doc_url,
            ];
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
            $document = $transaction->documents()->first();
            $url = $azureStorageUrl.$azureStorageContainer.'/'.$document->doc_url;

            return $url;

        }
    }
}
