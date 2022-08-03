<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    protected $table = 'payment_status_log';

    public function payments()
    {
        return $this->hasMany('App\Models\Payment', 'payment_code', 'code');
    }
}
