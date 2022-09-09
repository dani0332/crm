<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    protected $table = 'payment_status_log';
    protected $fillable = ['current_payment_status_id', 'payment_code', 'created_at', 'updated_at', 'previous_payment_status_id'];

    public function payment()
    {
        return $this->belongsTo('App\Models\Payment', 'code', 'payment_code');
    }
}
