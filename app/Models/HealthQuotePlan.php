<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthQuotePlan extends Model
{
    use HasFactory;
    protected $table = 'health_quote_plans';
    protected $guarded = ['id'];
}
