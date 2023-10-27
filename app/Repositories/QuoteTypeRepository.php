<?php

namespace App\Repositories;

use App\Enums\QuoteTypeId;
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
        return $this->withActive()->orderBy('sort_order')->get();
    }

    public function fetchAllowedQuoteForAml()
    {
        $notAllowedQuoted = [QuoteTypeId::Yacht, QuoteTypeId::Bike, QuoteTypeId::Jetski, QuoteTypeId::Cycle];

        return $this->whereNotIn('id', $notAllowedQuoted)->withActive()->orderBy('sort_order')->get();
    }
}
