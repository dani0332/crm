<?php

namespace App\Repositories;

use App\Models\PaymentMethod;
use App\Models\PaymentStatusLog;

class PaymentMethodRepository extends BaseRepository
{
    public function model() {
        return PaymentMethod::class;
    }


}
