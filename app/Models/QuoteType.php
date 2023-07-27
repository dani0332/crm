<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteType extends Model
{
    use HasFactory;

    protected $table = 'quote_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function insuranceProviders()
    {
        return $this->belongsToMany(InsuranceProvider::class, 'insurer_quote_type_mapping', 'quote_type_id', 'insurance_provider_id');
    }
}
