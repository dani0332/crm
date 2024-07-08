<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarQuoteRequestDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_quote_request_detail';
    protected $guarded = [];

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

    public function getAdvisorAssignedDateAttribute($table)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return isValidDate($table)
            ? $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format)
            : $table;
    }

    public function assignedBy()
    {
        return $this->hasOne(User::class, 'id', 'advisor_assigned_by_id');
    }

    public function lostReason()
    {
        return $this->hasOne(LostReasons::class, 'id', 'lost_reason_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carQuote()
    {
        return $this->belongsTo(CarQuote::class, 'car_quote_request_id');
    }
}
