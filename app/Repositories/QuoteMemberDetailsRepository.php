<?php

namespace App\Repositories;

use App\Models\QuoteMemberDetail;

class QuoteMemberDetailsRepository extends BaseRepository
{
    public function model()
    {
        return QuoteMemberDetail::class;
    }

    public function fetchGetBy($column, $value, $quoteTypeId)
    {
        return $this->byQuoteTypeId($quoteTypeId)
            ->where($column, $value)
            ->with([
                'relation',
                'nationality',
            ])->get();
    }

}
