<?php

namespace App\Models;

use App\Enums\PaymentProcessJobEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcPaymentProcessJob extends Model
{
    use HasFactory;

    protected $table = 'cc_payment_processes';

    // Define a scope to filter by status
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentProcessJobEnum::FAILED_STATUS);
    }

    public function splitPayment()
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
