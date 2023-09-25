<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class InslyDetail extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'insly-details';
    protected $casts = ['createdAt' => 'datetime', 'updatedAt' => 'datetime'];
    protected $dates = ['policy.end_date', 'policy.start_date'];
}
