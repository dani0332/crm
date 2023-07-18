<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentlyLocatedIn extends Model
{
    use HasFactory;
    protected $table = 'insurance_provider';
    protected $guarded = ['id'];
}
