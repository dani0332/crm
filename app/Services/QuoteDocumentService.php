<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\QuoteDocument;

class QuoteDocumentService extends BaseService
{
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

    public function createQuoteDocumentRecord($documentTypeCode, $fileNameOriginal, $filePathAzure, $fileMimeType, $quoteModel)
    {
        $documentTypeCode_ = DocumentType::where('code', $documentTypeCode)->first();

        if (! $documentTypeCode_) {
            return false;
        }

        $docUuid = uniqid();

        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        $quoteDocument = QuoteDocument::create([
            'doc_name' => $fileNameOriginal,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_code' => $documentTypeCode_->code,
            'document_type_text' => $documentTypeCode_->text,
            'doc_uuid' => $docUuid,
            'created_by_id' => auth()->id(),
        ]);

        $quoteModel->documents()->save($quoteDocument);
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
        if (! $record->policy_number || ! $record->policy_issuance_date || ! $record->policy_start_date || ! $record->renewal_expiry_date || ! $record->premium) {
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
        if ($quote) {
            return $quote->documents->sortDesc();
        } else {
            return [];
        }
    }
}
