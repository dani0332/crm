<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class TravelMemberDetail extends Model
{
    use HasFactory;
    protected $table = 'travel_quote_request_member_details';
    protected $guarded = ['id'];

    public function travelQuote()
    {
        return $this->belongsTo(TravelQuote::class, 'id', 'primary_member_id'); 
    }

    public function getDobAttribute($value){
        return Carbon::parse($value)->format('d-m-Y');
    }
}
