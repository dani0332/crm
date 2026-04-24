<?php

namespace App\Models;

use App\Traits\QuoteModelTrait;
use MongoDB\Laravel\Eloquent\Model;

class UaeSigningPassLog extends Model
{
    use QuoteModelTrait;

    protected $connection = 'mongodb';
    protected $table = 'uae-pass-sign-in-logs';
    protected $guarded = [];
}
