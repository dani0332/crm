<?php

namespace App\Strategies\EmbeddedProducts;

use App\Services\SukoonDemocranceService;
use Carbon\Carbon;
use finfo;
use Illuminate\Support\Facades\Storage;

class AlfredProtect extends EmbeddedProduct
{
    /**
     * Retrieves the Certificate for a quote object.
     *
     * @param  object  $quoteObject
     * @param  string  $certificateNumber
     * @param  float  $premium
     * @return array
     */
    public function getCertificateDocument($ep, $transaction, $quoteObject)
    {
        $documents = $ep->documents()->count();
        if ($documents > 0) {
            $document = $ep->documents()->first();
            $file = Storage::disk('azureIM')->get($document->doc_url);

            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->buffer($file);

            return [
                'Content' => base64_encode($file),
                'Name' => $document->doc_name,
                'ContentType' => $mimeType,
            ];
        } else {
            $sukoonDemocrance = new SukoonDemocranceService();
            $sukoonDemocrance->processDemocranceSubmission($quoteObject, $ep, $transaction);
            $document = $ep->documents()->first();
            $file = Storage::disk('azureIM')->get($document->doc_url);

            return [
                'Content' => base64_encode($file),
                'Name' => $document->doc_name,
                'ContentType' => 'application/pdf',
            ];
        }
    }
}
