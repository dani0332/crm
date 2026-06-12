<?php

namespace App\Models;

use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Model;

class UaePassLog extends Model
{
    use QuoteModelTrait;

    protected $table = 'uae_pass_logs';
    protected $guarded = [];
}
