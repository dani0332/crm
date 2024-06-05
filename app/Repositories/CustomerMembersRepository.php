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

    public function fetchGetBy($quote_request_id, $quoteType, $customerType = CustomerTypeEnum::Individual)
    {
        $quoteModelObject = $this->getModelObject(strtolower($quoteType));

        return $this->where([
            'quote_type' => ltrim($quoteModelObject, '\\'),
            'quote_id' => $quote_request_id,
            'customer_type' => $customerType,
            'deleted_at' => null,
        ])->with([
            'relation',
            'emirate',
            'nationality',
        ])->get();
    }

    public function fetchGetMemberInfo($column, $value, $quoteType, $customerType = CustomerTypeEnum::Individual, $selectedColumns = 'id')
    {
        return $this->byQuoteType($quoteType)
            ->where([
                $column => $value,
                'customer_type' => $customerType,
            ])
            ->select($selectedColumns)->get()->toArray();
    }
}
