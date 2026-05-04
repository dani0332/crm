<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCharge extends Model
{
    use SpatieActivityLog;

    public function paymentSplit(): BelongsTo
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
