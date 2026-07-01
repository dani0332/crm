<?php

namespace App\Models;

use App\Enums\QuoteTypeId;
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
            QuoteTypeId::Car,
            QuoteTypeId::Travel,
            QuoteTypeId::Home,
            QuoteTypeId::Pet,
            QuoteTypeId::Bike,
            QuoteTypeId::Cycle,
            QuoteTypeId::Jetski,
            QuoteTypeId::Business,
            QuoteTypeId::Yacht,
            QuoteTypeId::Health,
            QuoteTypeId::Life,
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

    public function businessActivities()
    {
        return $this->belongsToMany(
            BusinessActivity::class,
            'business_activity_quote_type_mapping',
            'quote_type_id',
            'business_activity_id'
        );
    }
}
