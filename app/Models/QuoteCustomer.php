<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteCustomer extends Model
{
    use HasFactory;

    protected $table = 'quote_customers';
    protected $guarded = [];
}
