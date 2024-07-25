<?php

namespace App\Strategies\EmbeddedProducts;

use App\Services\SukoonDemocranceService;
use Carbon\Carbon;
use finfo;
use Illuminate\Support\Facades\Storage;

class AlfredProtect extends EmbeddedProduct
{
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
                'Content' => base64_encode($file),
                'Name' => $document->doc_name,
                'ContentType' => $mimeType,
                'Path' => $filePath,
                'file' => $file
            ];
        } else {
            $sukoonDemocrance = new SukoonDemocranceService();
            $sukoonDemocrance->processDemocranceSubmission($quoteObject, $ep, $transaction);
            $document = $transaction->documents()->first();
            $file = Storage::disk('azureIM')->get($document->doc_url);
            $filePath = Storage::disk('azureIM')->url($document->doc_url);

            return [
                'Content' => base64_encode($file),
                'Name' => $document->doc_name,
                'ContentType' => 'application/pdf',
                'Path' => $filePath,
                'file' => $file
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
            $url = $azureStorageUrl . $azureStorageContainer . '/' . $document->doc_url;
            return $url;

        } else {
            $sukoonDemocrance = new SukoonDemocranceService();
            $sukoonDemocrance->processDemocranceSubmission($quoteObject, $ep, $transaction);
            $document = $transaction->documents()->first();

            $url = $azureStorageUrl . $azureStorageContainer . '/' . $document->doc_url;
            return $url;
        }
    }
}
