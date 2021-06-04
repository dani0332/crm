<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfInsurance extends Model
{
    use HasFactory;
    protected $table = 'type_of_insurances';
    public $timestamps = false;
}
