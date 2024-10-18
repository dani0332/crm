<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class InslyDetail extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'insly-details';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d', 'policy.end_date' => 'datetime:Y-m-d', 'policy.start_date' => 'datetime:Y-m-d'];
    protected $dates = ['policy.end_date', 'policy.start_date'];
}
