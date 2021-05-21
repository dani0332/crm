<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    protected $table = 'partner';
    

    public function rewards()
    {
        return $this->hasMany(Reward::class,'id','partner_id');
    }
}
