<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lookup extends Model
{
    use HasFactory;

    // public function scopeWithChilderns($query, $quoteTypeId) 
    // {
    //     return $query->with('childs');
    // }

    public function childs() 
    {
        return $this->hasMany('App\Models\Lookup', 'parent_id', 'id');
    }
}
