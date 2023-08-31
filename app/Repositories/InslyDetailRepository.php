<?php

namespace App\Repositories;

use App\Models\InslyDetail;

class InslyDetailRepository extends BaseRepository
{
    public function model()
    {
        return InslyDetail::class;
    }
    public function fetchGetData()
    {
        $query = InslyDetail::query();
        if (! empty(request()->policy_number)) {
            $query->where('policy_no', '=', request()->policy_number);
        }
        if (! empty(request()->email)) {
            $query->where('customer.email', '=', request()->email);
        }
        if (! empty(request()->mobile_no)) {
            $query->where('customer.mobile_phone', '=', request()->mobile_no);
        }
        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;

    }



    public function fetchGetBy($column, $value)
    {
        $policy = $this->where($column, $value)->firstOrFail();

        $data= $policy->toArray();
        if(!empty($data['installments'])){
            $policy->premium= collect($data['installments'])->sum('gross_premium');
        }
        return $policy;

    }

}
