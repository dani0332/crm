<?php

namespace App\Models;
class Payment extends CRMBaseModel
{
    protected $table = 'payments';

    public function paymentMethod()
    {
        return $this->morphTo();
    }
}
