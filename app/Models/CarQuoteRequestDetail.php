<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Support\Facades\Config;

class CarQuoteRequestDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_quote_request_detail';
    protected $guarded = [];
    protected $appends = [
        'advisor_assigned_date_formatted',
        'next_followup_date_formatted',
    ];

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

    public function advisorAssignedDateFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->advisor_assigned_date ? Carbon::parse($this->advisor_assigned_date)->format('d-m-Y H:i:s') : null;
            }
        );
    }

    public function nextFollowupDateFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->next_followup_date ? Carbon::parse($this->next_followup_date)->format('d-m-Y') : null;
            }
        );
    }

    public function driverNationality()
    {
        return $this->belongsTo(Nationality::class, 'driver_nationality_id');
    }
}
