<?php

namespace App\Models;

use App\Traits\Optionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubArea extends Model
{
    use HasFactory, Optionable;

    protected $table = 'sub_areas';

    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirates_id');
    }
}
