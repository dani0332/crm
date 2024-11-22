<?php

namespace App\Repositories;


use App\Models\PaymentStatus;

class PaymentStatusRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return PaymentStatus::class;
    }

    public function fetchGetList($except = [], $orderBy = 'sort_order', $order = 'asc')
    {
        return $this->whereNotIn('id', $except)->withActive()->orderBy($orderBy, $order)->get();
    }
}
