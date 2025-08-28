<?php

namespace App\Services\Life;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Services\BaseService;
use App\Traits\GenericQueriesAllLobs;

class CustomerMemberService extends BaseService
{
    use GenericQueriesAllLobs;

    public function getBy($quote_request_id, $quoteType, $customerType = CustomerTypeEnum::Individual)
    {
        $quoteModelObject = $this->getModelObject(strtolower($quoteType));

        return CustomerMembers::where([
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
}
