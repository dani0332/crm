<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InputCanonicalEnumMapping extends Model
{
    protected $fillable = [
        'input_type',
        'input_value',
        'canonical_enum',
    ];
}
