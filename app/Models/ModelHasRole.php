<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    use HasFactory, SpatieActivityLog;

    protected $table = 'model_has_roles';
}
