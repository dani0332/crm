<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Activities extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'activities';

    public function getCreatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getDueDateAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}
