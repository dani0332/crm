<?php

namespace App\Repositories;

use App\Models\PaymentStatus;

class PaymentStatusRepository extends BaseRepository
{
    public function model()
    {
        return PaymentStatus::class;
    }

}
