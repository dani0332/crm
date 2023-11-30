<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelMemberDetail extends Model
{
    use HasFactory;

    protected $table = 'travel_quote_request_member_details';
    protected $guarded = ['id'];

    public function travelQuote()
    {
        return $this->belongsTo(TravelQuote::class, 'id', 'primary_member_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function relation()
    {
        return $this->belongsTo(Lookup::class, 'relation_code', 'code');
    }

    public function getDobAttribute($value)
    {
        return ! empty($value) ? Carbon::parse($value)->format('Y-m-d') : $value;
    }
}
