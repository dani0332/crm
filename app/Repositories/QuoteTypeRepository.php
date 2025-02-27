<?php

namespace App\Repositories;

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
        $notAllowedQuoted = [];

        return $this->whereNotIn('id', $notAllowedQuoted)->withActive()->orderBy('sort_order')->get();
    }

    public function fetchGetById($quoteTypeId)
    {
        return $this->where('id', $quoteTypeId)->first();
    }
}
