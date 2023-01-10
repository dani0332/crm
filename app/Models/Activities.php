<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Activities extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'activitìes';

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getDueDateAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function assignee()
    {
        return $this->hasOne(User::class, 'id', 'assignee_id');
    }
}
