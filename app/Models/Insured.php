<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insured extends Model
{
    protected $table = 'insured';
    protected $guarded = [];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }
}
