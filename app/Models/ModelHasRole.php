<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SpatieActivityLog;

class ModelHasRole extends Model
{
    use HasFactory, SpatieActivityLog;

    protected $table = 'model_has_roles';
}
