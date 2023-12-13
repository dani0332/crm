<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;

class GenericQuoteRepository extends BaseRepository
{
    public function model()
    {
        return $this->getModelNameForDocument(request()->folder_path ?? '');
    }

    public function getModelNameForDocument($quoteType = 'personal')
    {
        $quoteType = ucfirst($quoteType);
        switch ($quoteType) {
            case quoteTypeCode::Business:
                $quoteType = quoteTypeCode::Business;
                break;
            case quoteTypeCode::Home:
                $quoteType = quoteTypeCode::Home;
                break;
            case in_array($quoteType, [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht]):
                $quoteType = QuoteTypes::PERSONAL->value;
                break;
            case quoteTypeCode::Travel:
                $quoteType = quoteTypeCode::Travel;
                break;
        }

        return 'App\\Models\\'.$quoteType.'Quote';
    }
}
