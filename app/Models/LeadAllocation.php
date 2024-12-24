<?php

namespace App\Models;

use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeadAllocation extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'lead_allocation';
    protected $fillable = ['is_hardstop'];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function leadAllocationUser()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function scopeTravelQuote($query)
    {
        return $query->where('quote_type_id', QuoteTypes::TRAVEL->id());
    }

    public function scopeActiveUser($query)
    {
        $query->whereHas('leadAllocationUser', function ($q) {
            $q->where('is_active', 1);
        });
    }
}
