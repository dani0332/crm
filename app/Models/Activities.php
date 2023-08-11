<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;

class Activities extends Model implements AuditableContract
{

    use HasFactory, Auditable, FilterCriteria;

    protected $guarded = [];
    protected $table = 'activities';
    public $filterables = [
        'status'        => FilterTypes::EXACT,
        'assignee_id'   => FilterTypes::EXACT,
        'due_date'   => FilterTypes::DATE_BETWEEN,
    ];

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

    public function assignee()
    {
        return $this->hasOne(User::class, 'id', 'assignee_id');
    }
}
