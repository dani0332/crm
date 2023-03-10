<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CycleQuote extends Model
{
    use HasFactory;

    protected $table = 'cycle_quote_request';
    protected $fillable = ['cycle_make', 'cycle_model', 'year_of_manufacture_id', 'accessories', 'has_accident', 'has_good_condition'];
}
