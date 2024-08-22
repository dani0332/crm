<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SICConfigable extends Model
{
    use HasFactory;
    protected $table = 'sic_configables';

    public function configable()
    {
        return $this->morphTo();
    }
}
