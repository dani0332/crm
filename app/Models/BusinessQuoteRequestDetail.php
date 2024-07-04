<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BusinessQuoteRequestDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'business_quote_request_detail';
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
        $date_time_format = Config::get('constants.datetime_format');

        return isValidDate($table)
            ? $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format)
            : $table;
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'advisor_assigned_by_id');
    }

    public function lostReason()
    {
        return $this->belongsTo(LostReasons::class);
    }
}
