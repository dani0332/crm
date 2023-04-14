<?php

namespace App\Repositories;

use App\Models\PaymentMethod;

class PaymentMethodRepository extends BaseRepository
{
    public function model()
    {
        return PaymentMethod::class;
    }
}
