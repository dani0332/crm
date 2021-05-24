<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarQoute extends Model
{
    use HasFactory;
    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];
}
