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

    public function insuredKyc()
    {
        return $this->hasOne(InsuredKyc::class, 'insured_id', 'id');
    }

    public function customerInsured()
    {
        return $this->hasOne(\App\Models\CustomerInsured::class, 'insured_id', 'id');
    }
}
