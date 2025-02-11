<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCharge extends Model
{
    public function paymentSplit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
