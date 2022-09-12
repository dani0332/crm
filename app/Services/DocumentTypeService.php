<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\QuoteDocument;

class DocumentTypeService extends BaseService
{
    /**
     * get list of active document types can be presented to customer to upload documents
     * @return mixed
     */
    public function getCustomerAllowedTypes()
    {
        return DocumentType::where([
            'is_active'                 => 1,
            'receive_from_customer'     => 1,
        ])->get();
    }
}
