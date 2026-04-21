<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InputCanonicalEnumMapping extends Model
{
    protected $table = 'input_canonical_enum_mapping';
    protected $fillable = [
        'input_type',
        'input_value',
        'canonical_enum',
    ];
}
