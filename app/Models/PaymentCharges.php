<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCharges extends Model
{
    public function paymentSplit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
