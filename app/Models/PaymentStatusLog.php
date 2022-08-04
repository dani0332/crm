<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    protected $table = 'payment_status_log';

    public function payment()
    {
        return $this->belongsTo('App\Models\Payment', 'code', 'payment_code');
    }
}
