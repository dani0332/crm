<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JetskiQuote extends Model
{
    use HasFactory;
    protected $table = 'jetski_quote_request';
    protected $guarded = [];
}
