<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthMemberDetail extends Model
{
    use HasFactory;

    protected $table = 'health_quote_request_member_details';
    protected $guarded = ['id'];

    public function healthQuote()
    {
        return $this->belongsTo(HealthQuote::class, 'id', 'primary_member_id');
    }

    public function emirate()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_your_visa_id');
    }

    public function memberCategory()
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id', 'id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function salaryBand()
    {
        return $this->belongsTo(SalaryBand::class, 'salary_band_id', 'id');
    }

    public function relation()
    {
        return $this->belongsTo(Lookup::class, 'relation_code', 'code');
    }

    public function getDobAttribute($value)
    {
        return ! empty($value) ? Carbon::parse($value)->format('Y-m-d') : $value;
    }

    public function documents()
    {
        return $this->hasMany(QuoteDocument::class, 'member_detail_id');
    }

}
