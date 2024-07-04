<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SICHealthConfig extends Model
{
    protected $table = "sic_health_configs";
    protected $guarded = [];
    protected $casts = [
        'plan_types' => 'json',
        'member_categories' => 'json',
        'nationalities'=> 'json',
    ];

}

