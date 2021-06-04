<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubTypeOfInsurance extends Model
{
    use HasFactory;
    protected $table = 'sub_type_of_insurances';
    public $timestamps = false;
}
