<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSplits extends Model
{
    use HasFactory;

    protected $table = 'payment_splits';
    
    public function splitPayments()
    {
        return $this->belongsTo(Payment::class, 'code', 'code');
    }
}
