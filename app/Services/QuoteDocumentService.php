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

	public function listQuoteDocumentsForUpload($quoteTypeId)
	{
		return DocumentType::where(['quote_type_id' => $quoteTypeId, 'is_active' => true])
        ->orderBy('sort_order', 'asc')
        ->get();
	}


}
