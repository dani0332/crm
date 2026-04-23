<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UaeSigningPassLog extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'uae_signing_pass_logs';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];
}
