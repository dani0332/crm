<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PaymentProcessJobEnum;

class CcPaymentProcessJob extends Model
{
    use HasFactory;
    protected $table = 'cc_payment_process_jobs';

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
