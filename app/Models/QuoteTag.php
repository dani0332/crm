<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteTag extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'quote_tags';
}
