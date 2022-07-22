<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\QuoteDocument;

class QuoteDocumentService extends BaseService
{
	public function getQuoteDocuments($quoteTypeId, $quoteId)
	{
		return QuoteDocument::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('created_at', 'desc')
        ->get();
	}

	public function getQuoteDocumentsForUpload($quoteTypeId)
	{
		return DocumentType::where(['quote_type_id' => $quoteTypeId, 'is_active' => true])
        ->orderBy('sort_order', 'asc')
        ->get();
	}

	public function createQuoteDocumentRecord($documentTypeCode, $fileNameOriginal, $filePathAzure, $fileMimeType, $travelQuote)
	{
		$documentTypeCode_ = DocumentType::where('code', $documentTypeCode)->first();

		if(!$documentTypeCode_) {
			return false;
		}

		$azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

		$quoteDocument = QuoteDocument::create([
            'doc_name'=> $fileNameOriginal,
            'doc_url'=> $azureStorageUrl.$azureStorageContainer.'/'.$filePathAzure,
            'doc_mime_type' => $fileMimeType,
			'document_type_code' => $documentTypeCode_->code,
			'document_type_text' => $documentTypeCode_->text,
			'created_by_id' => auth()->id()
        ]);

        $travelQuote->documents()->save($quoteDocument);
	}

}
