<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class UaeSigningPassLog extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'uae-pass-sign-in-logs';
    protected $guarded = [];
}
