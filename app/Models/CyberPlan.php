<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CyberPlan extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'cyber-quote-plan-details';
    protected $guarded = [];
}
