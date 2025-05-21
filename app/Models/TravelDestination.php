<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelDestination extends Model
{
    use HasFactory;

    protected $table = 'travel_destination';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quote_id',
        'uuid',
        'destination_id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    public function destination()
    {
        return $this->belongsTo(Nationality::class, 'destination_id');
    }
}
