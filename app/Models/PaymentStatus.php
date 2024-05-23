<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentStatus extends Model
{
    use HasFactory;

    protected $hidden = ['text_ar'];
    protected $table = 'payment_status';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
