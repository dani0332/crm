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
            $document = $transaction->documents()->first();
            $url = $azureStorageUrl.$azureStorageContainer.'/'.$document->doc_url;

            return $url;

        }
    }
}
