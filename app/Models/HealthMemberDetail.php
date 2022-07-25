<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class HealthMemberDetail extends Model
{
    use HasFactory;
    protected $table = 'Health_quote_request_member_details';
    protected $guarded = ['id'];

    public function healthQuote()
    {
        return $this->belongsTo(HealthQuote::class, 'id', 'primary_member_id'); 
    }
    public function memberCategory(){
        return $this->belongsTo(MemberCategory::class,'member_category_id','id');
    }
    public function salaryBand(){
        return $this->belongsTo(SalaryBand::class,'salary_band_id','id');
    }

    public function getDobAttribute($value){
        return Carbon::parse($value)->format('Y-m-d');
    }
}
