<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Activities extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'activitìes';

    public function getCreatedAtAttribute($table)
    {
        return $this->asDateTime($table)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getDueDateAttribute($table)
    {
        return $this->asDateTime($table)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}
