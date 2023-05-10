<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Models\QuoteDocument;

class QuoteDocumentRepository extends BaseRepository
{
    public function model()
    {
        return QuoteDocument::class;
    }

    public function fetchIsEnabled($quoteModelType)
    {
        $enabledLOBs = [quoteTypeCode::Car, quoteTypeCode::Health];
        if (in_array($quoteModelType, $enabledLOBs)) {
            return true;
        }

        return false;
    }
}
