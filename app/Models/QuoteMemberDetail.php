<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteMemberDetail extends Model
{
    use HasFactory;

    protected $table = 'quote_member_details';
    protected $guarded = ['id'];

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id');
    }

    public function relation()
    {
        return $this->belongsTo(Lookup::class, 'relation_code', 'code');
    }

    public function getDobAttribute($value)
    {
        return Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY'));
    }

}
