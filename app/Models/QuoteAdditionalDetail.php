<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class QuoteAdditionalDetail extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'quote-additional-details';
}
