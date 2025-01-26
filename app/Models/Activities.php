<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Activities extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $guarded = [];
    protected $table = 'activities';
    public $filterables = [
        'status' => FilterTypes::EXACT,
        'assignee_id' => FilterTypes::EXACT,
        'due_date' => FilterTypes::DATE_BETWEEN,
        'activity_type' => FilterTypes::EXACT,
    ];
    protected $appends = ['is_overdue'];

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
    // Added custom field to verify over due date
    public function getIsOverdueAttribute()
    {
        $dateFormat = config('constants.DATETIME_DISPLAY_FORMAT');
        $formatedDueDate = Carbon::parse($this->due_date)->format($dateFormat);
        $dueDate = Carbon::createFromFormat($dateFormat, $formatedDueDate);
        $now = now()->format($dateFormat);

        return $dueDate->lt($now);
    }

    public function assignee()
    {
        return $this->hasOne(User::class, 'id', 'assignee_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }
}
