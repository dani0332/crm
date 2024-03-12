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

    public function fetchGetList($codes = [])
    {
        $query = $this->withActive()
                    ->orderBy('sort_order');

        if(!empty($codes)) {
            $query = $query->whereIn('code', $codes);
        }

        return $query->get();
    }

    public function fetchAllowedQuoteForAml()
    {
        $notAllowedQuoted = [];

        return $this->whereNotIn('id', $notAllowedQuoted)->withActive()->orderBy('sort_order')->get();
    }
}
