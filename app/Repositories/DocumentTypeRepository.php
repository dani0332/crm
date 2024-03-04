<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;

class DocumentTypeRepository extends BaseRepository
{
    public function model()
    {
        return DocumentType::class;
    }

    public function getSendPolicyDocumentCodes($quoteType){
        $documentTypeCodes= DocumentType::requiredForSendPolicy()->where('quote_type_id', QuoteTypes::getQuoteId($quoteType))->pluck('code')->toArray();
        if($quoteType == QuoteTypes::GROUP_MEDICAL->value) {
            $documentTypeCodes= array_merge($documentTypeCodes, array('REC_GH', 'NL_GH'));
        }
        return $documentTypeCodes;
    }

    public function getTaxDocumentsCode($quoteType){
        return DocumentType::taxDocument()->where('quote_type_id', QuoteTypes::getQuoteId($quoteType))->pluck('code')->toArray();
    }
}
