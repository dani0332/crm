<?php

namespace App\Repositories;

use App\Models\PaymentStatusLog;

class PaymentStatusLogRepository extends BaseRepository
{
    public function model()
    {
        return PaymentStatusLog::class;
    }
}
