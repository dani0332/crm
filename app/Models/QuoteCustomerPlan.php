<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteCustomerPlan extends Model
{
    protected $casts = [
        'plan' => 'array',
    ];
}
