<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarQuoteValuation extends Model
{
    use HasFactory;

    protected $table = 'car_quote_valuation';
    public $casts = [
        'insurer_available_trims' => 'array',
    ];
}
