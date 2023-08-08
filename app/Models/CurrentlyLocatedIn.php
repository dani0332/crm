<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentlyLocatedIn extends Model
{
    use HasFactory;

    protected $table = 'currently_located_in';
    protected $guarded = ['id'];
}
