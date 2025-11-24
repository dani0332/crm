<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SpatieActivityLog;

class PaymentCharge extends Model
{
    use SpatieActivityLog;
    
    public function paymentSplit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
