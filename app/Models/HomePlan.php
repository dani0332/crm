<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HomePlan extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'home-quote-plan-details';
}
