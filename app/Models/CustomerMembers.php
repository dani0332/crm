<?php

namespace App\Models;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMembers extends Model
{
    use GenericQueriesAllLobs, HasFactory;

    protected $guarded = ['id'];

    protected $appends = [
        'name',
    ];

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function setNameAttribute($value)
    {
        $quoteType = request()->quote_type;
        if(ucwords($quoteType) == QuoteTypes::TRAVEL->value){
            $nameParts = explode(' ', $value, 2);
            $this->attributes['first_name'] = $nameParts[0];
            $this->attributes['last_name'] = $nameParts[1] ?? '';
        }else{
            $this->attributes['first_name'] = $value;
        }

    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function relation()
    {
        return $this->belongsTo(Lookup::class, 'relation_code', 'code');
    }

    public function emirate()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_your_visa_id');
    }

    public function memberCategory()
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id', 'id');
    }

    public function salaryBand()
    {
        return $this->belongsTo(SalaryBand::class, 'salary_band_id', 'id');
    }

    public function quote()
    {
        return $this->morphTo();
    }

    public function getDobAttribute($value)
    {
        return ! empty($value) ? Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY')) : $value;
    }

    /**
     * @return mixed
     */
    public function scopeByQuoteType($query, $quoteType)
    {
        $quoteModelObject = $this->getModelObject(strtolower($quoteType));

        return $query->whereHasMorph('quote', $quoteModelObject);
    }

    public function getAgeAttribute()
    {
        $dob = Carbon::parse($this->dob);

        return $dob->age;
    }
}
