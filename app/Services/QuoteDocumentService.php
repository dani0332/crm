<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Log;

class QuoteDocumentService extends BaseService
{
    use GenericQueriesAllLobs;

    /**
     * get list of active document types can be presented to customer to upload documents.
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
        if (! auth()->user()->hasRole(RolesEnum::BetaUser)) {
            return false;
        }

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
     * @param $quoteType
     * @param $data doc_name, doc_uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteQuoteDocument($quoteType, $data)
    {
        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

        //load quote document with provided detail
        $quote->load(['documents' => function ($q) use ($data) {
            $q->where([
                'doc_name' => $data['doc_name'],
                'doc_uuid' => $data['doc_uuid'],
            ]);
        }]);

        //check for document and delete if found
        if (($document = $quote->documents->first())) {
            $document->delete();
            Log::info('CL: '.get_class().' FN: deleteQuoteDocument  UUID: '.$data['quote_uuid'].' Message: document ('.$data['doc_name'].') deleted');

            return response()->json(['message' => 'document deleted successfully']);
        }

        vAbort('Invalid document detail provided.');
    }

    /**
     * upload quote document and store document record in db.
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
            $originalName = $file->getClientOriginalName();
            $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
            $fileMimeType = $file->getClientMimeType();

            //upload file to azure
            $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
            $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

            //generate unique uuid
            $docUuid = uniqid();
            while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
                $docUuid = uniqid().rand(1, 100);
            }

            return $quote->documents()->create([
                'doc_name' => $docName,
                'original_name' => $originalName,
                'doc_url' => $filePathAzure,
                'doc_mime_type' => $fileMimeType,
                'document_type_code' => $documentType->code,
                'document_type_text' => $documentType->text,
                'doc_uuid' => $docUuid,
                'created_by_id' => auth()->id(),
            ]);
        } catch (\Exception $exception) {
            Log::info('CL: '.get_class().'FN: uploadQuoteDocument  UUID: '.$data['quote_uuid'].' Error Code/Message: '.$exception->getCode().'/'.$exception->getMessage());

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
            ! isset($record->premium) || ! isset($record->renewal_expiry_date) || ! isset($record->plan_id) ||
            $record->advisor_id != auth()->user()->id) {
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
