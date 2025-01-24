<?php

namespace App\Models;

use App\Enums\PaymentProcessJobEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcPaymentProcess extends Model
{
    use HasFactory;

    protected $table = 'cc_payment_processes';
    protected $fillable = [
        'payment_splits_id',
        'quote_type',
        'quoteable_id',
        'amount_captured',
        'status',
    ];

    // Define a scope to filter by status
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentProcessJobEnum::FAILED);
    }

    public function splitPayment()
    {
        return $this->belongsTo(PaymentSplits::class, 'payment_splits_id', 'id');
    }

    public function getQuoteTypeAttribute($value)
    {
        return ucfirst(strtolower($value));
    }

}
