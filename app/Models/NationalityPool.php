<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalityPool extends Model
{
    protected $table = 'nationality_pool';
    protected $fillable = [
        'effective_from',
        'effective_to',
        'canonical_nationality_code',
    ];
}
