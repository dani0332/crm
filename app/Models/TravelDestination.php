<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelDestination extends Model
{
    use HasFactory;

    protected $table = 'travel_destination';

    public function destination()
    {
        return $this->belongsTo(Nationality::class, 'destination_id');
    }
}
