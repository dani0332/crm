<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enum\PaymentProcessJobEnum;

class CcPaymentProcessJob extends Model
{
    use HasFactory;
    protected $table = 'cc_payment_process_jobs';

    // Define a scope to filter by status
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function splitPayment()
    {
        return $this->belongsTo(PaymentSplits::class);
    }
}
