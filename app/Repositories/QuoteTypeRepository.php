<?php

namespace App\Repositories;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\QuoteType;

class QuoteTypeRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return QuoteType::class;
    }

    public function fetchGetList($orderBy = 'sort_order', $order = 'asc')
    {
        return $this->withActive()->orderBy($orderBy, $order)->get();
    }

    public function fetchAllowedQuoteForAml()
    {
        $notAllowedQuoted = [QuoteTypeId::CompanyCar];
        $notAllowedQuoteTypeCodes = [QuoteTypes::GROUP_MEDICAL->value, QuoteTypes::CORPLINE->value];

        return $this->whereNotIn('id', $notAllowedQuoted)->whereNotIn('code', $notAllowedQuoteTypeCodes)->withActive()->orderBy('sort_order')->get();
    }

    public function fetchGetById($quoteTypeId)
    {
        return $this->where('id', $quoteTypeId)->first();
    }
}
