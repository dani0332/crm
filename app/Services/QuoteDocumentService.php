<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use Illuminate\Support\Facades\Log;

class QuoteDocumentService extends BaseService
{
    /**
     * get list of active document types can be presented to customer to upload documents
     *
     * @param $quoteTypeId
     * @return mixed
     */
    public function getQuoteDocumentsToReceive($quoteTypeId)
    {
        return DocumentType::where([
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => $quoteTypeId,
        ])
        ->orderBy('sort_order')
        ->get();
    }

    public function isEnabled($quoteModelType)
    {
        $enabledLOBs = [quoteTypeCode::Car];
        if (in_array($quoteModelType, $enabledLOBs)) {
            return true;
        }

        return false;
    }

    public function getQuoteDocumentsForUpload($quoteTypeId)
    {
        return DocumentType::where(['quote_type_id' => $quoteTypeId, 'is_active' => true])
        ->orderBy('sort_order', 'asc')
        ->get();
    }

    /**
     * upload quote document and store document record in db
     *
     * @param $file
     * @param $documentTypeCode
     * @param $uuid
     * @param $quote
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadQuoteDocument($file, $data, $quote)
    {
        if (! ($documentType = DocumentType::where('code', $data['document_type_code'])->first())) {
            return response()->json(['error' => 'Invalid document type code provided'], 500);
        }

        try {
            $fileNameOriginal = preg_replace('/\s+/', '', uniqid().'_'.$file->getClientOriginalName());
            $fileMimeType = $file->getClientMimeType();

            //upload file to azure
            $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$fileNameOriginal;
            $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

            //generate unique uuid
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            $quote->documents()->create([
                'doc_name' => $fileNameOriginal,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'created_by_id' => auth()->id(),
            ]);

            return response()->json(['message' => 'file uploaded successfully.']);
        } catch (\Exception $exception) {
            Log::info('FN: uploadQuoteDocument  UUID: '.$data['quote_uuid'].' Error Code/Message: '.$exception->getCode().'/'.$exception->getMessage());

            return response()->json(['error' => 'Document upload failed, please try again'], 500);
        }
    }

    public function getQuoteDocumentUrl($id)
    {
        $quoteDocument = QuoteDocument::where('doc_uuid', $id)->first();

        if (! $quoteDocument) {
            abort(404);
        }

        return (object) ['doc_url' => $quoteDocument->doc_url, 'doc_mime_type' => $quoteDocument->doc_mime_type];
    }

    public function showSendPolicyButton($record, $quoteDocuments, $quoteTypeId)
    {
        if (! $record) {
            return 0;
        }

        if (! isset($record->policy_number) || ! isset($record->policy_issuance_date) || ! isset($record->policy_start_date) ||
            ! isset($record->premium) || ! isset($record->renewal_expiry_date)) {
            return 0;
        }
        $documentUploadTypes = $this->getQuoteDocumentsForUpload($quoteTypeId);
        if (! $documentUploadTypes) {
            return 0;
        }
        $displaySendPolicyButton = 1;
        foreach ($documentUploadTypes->where('is_required', 1) as $documentUploadType) {
            if ($quoteDocuments->where('document_type_code', $documentUploadType->code)->count() == 0) {
                $displaySendPolicyButton = 0;
                break;
            }
        }

        return $displaySendPolicyButton;
    }

    public function getQuoteDocuments($quoteType, $recordId)
    {
        $quote = app()->make('App\\Models\\'.$quoteType.'Quote')::where('id', $recordId)->first();
        if ($quote && $quote->documents) {
            return $quote->documents->sortDesc();
        } else {
            return [];
        }
    }
}
