<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Models\QuoteMemberDetail;

class QuoteMemberDetailsRepository extends BaseRepository
{
    public function model()
    {
        return QuoteMemberDetail::class;
    }

    public function fetchGetBy($column, $value, $quoteTypeId, $customerType = CustomerTypeEnum::Individual)
    {
        return $this->byQuoteTypeId($quoteTypeId)
            ->where([
                $column => $value,
                'customer_type' => $customerType
            ])
            ->with([
                'relation',
                'nationality',
            ])->get();
    }

}
