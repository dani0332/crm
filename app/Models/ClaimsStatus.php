<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimsStatus extends Model
{
    use HasFactory;
    protected $table = 'claims_statuses';
    public $timestamps = false;
}
