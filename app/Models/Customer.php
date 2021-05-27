<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customer';


    public function nationality(){
        return $this->hasOne( Nationality::class , 'id','nationality_id');
    }
}
