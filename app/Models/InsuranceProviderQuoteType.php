<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProviderQuoteType extends Model
{
    use HasFactory;

    protected $table = 'insurance_provider_quote_type';
    protected $casts = [
        'per_member_price' => 'boolean',
    ];
}
