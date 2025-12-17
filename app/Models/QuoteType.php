<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class QuoteType extends Model
{
    use HasFactory;

    protected $table = 'quote_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to filter quote types for claims module
     */
    public function scopeForClaims($query)
    {
        return $query->whereIn('id', [
            \App\Enums\QuoteTypeId::Car,
            \App\Enums\QuoteTypeId::Travel,
            \App\Enums\QuoteTypeId::Home,
            \App\Enums\QuoteTypeId::Pet,
            \App\Enums\QuoteTypeId::Bike,
            \App\Enums\QuoteTypeId::Cycle,
            \App\Enums\QuoteTypeId::Jetski,
            \App\Enums\QuoteTypeId::Business,
            \App\Enums\QuoteTypeId::Yacht,
            \App\Enums\QuoteTypeId::Health,
            \App\Enums\QuoteTypeId::Life,
        ]);
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

    public function insurerProviders()
    {
        return $this->belongsToMany(InsuranceProvider::class, 'insurance_provider_quote_type');
    }
}
