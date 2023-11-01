<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    public function payments()
    {
        return $this->hasMany('App\Models\Payment');
    }

    public function childPaymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'parent_code', 'code');
    }
}
