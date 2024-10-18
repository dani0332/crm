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

    public function fetchGetList()
    {
        $query = $this->withActive()
            ->orderBy('sort_order');

        return $query->get();
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
