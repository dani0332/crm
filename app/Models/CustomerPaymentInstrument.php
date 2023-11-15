<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentInstrument extends Model
{
    use HasFactory;

    protected $table = 'customer_payment_instrument';
    protected $fillable = [
        'card_holder_name',
    ];
}
