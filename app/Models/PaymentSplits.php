<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSplits extends Model
{
    use HasFactory;

    protected $table = 'payment_splits';
    protected $fillable = ['code', 'sr_no', 'payment_method', 'check_detail', 'payment_amount', 'due_date', 'payment_status_id'];
    
    public function splitPayments()
    {
        return $this->belongsTo(Payment::class, 'code', 'code');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class,'payment_status_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }
}
