<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelDestination extends Model
{
    use HasFactory;

    protected $table = 'travel_destination';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quote_id',
        'uuid',
        'destination_id',
    ];

    public function destination()
    {
        return $this->belongsTo(Nationality::class, 'destination_id');
    }
}
