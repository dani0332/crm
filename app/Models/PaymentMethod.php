<?php

namespace App\Models;


class PaymentMethod extends CRMBaseModel
{

    protected $table = 'payment_methods';

    public function payments()
    {
        return $this->hasMany('App\Models\Payment');
    }
}
