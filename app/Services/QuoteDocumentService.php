<?php

namespace App\Services;

use App\Models\QuoteDocument;

class QuoteDocumentService extends BaseService
{
	public function getQuoteDocuments($quoteTypeId, $quoteId)
	{
		return QuoteDocument::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('created_at', 'desc')
        ->get();
	}

}
