<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Traits\GenericQueriesAllLobs;

class CustomerMembersRepository extends BaseRepository
{
    use GenericQueriesAllLobs;
    public function model()
    {
        return CustomerMembers::class;
    }

    public function fetchGetBy($column, $value, $quoteType, $customerType = CustomerTypeEnum::Individual)
    {
        return $this->byQuoteType($quoteType)
            ->where([
                $column => $value,
                'customer_type' => $customerType,
            ])
            ->with([
                'relation',
                'emirate',
                'nationality',
            ])->get();
    }
}
