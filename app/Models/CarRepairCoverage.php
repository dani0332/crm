<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRepairCoverage extends Model
{
    use HasFactory;
    protected $table = 'car_repair_coverages';
    public $timestamps = false;
}
