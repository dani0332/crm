<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Model;

class PaymentCharge extends Model
{
    use SpatieActivityLog;

    public function paymentSplit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
