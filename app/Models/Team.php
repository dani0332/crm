<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Team extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'teams';

    public function getCreatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->asDateTime($date)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function parent()
    {
        return $this->belongsTo(Team::class, 'parent_team_id');
    }

    public function children()
    {
        return $this->hasMany(Team::class, 'parent_team_id');
    }

    public function getTypeAttribute()
    {
        switch ($this->attributes['type']) {
            case 1:
                return 'Product';
            case 2:
                return 'Team';
            case 3:
                return 'Subteam';
            default:
                return '';
        }
    }

    public function getIsActiveAttribute()
    {
        return $this->attributes['is_active'] == 1 ? 'True' : 'False';
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_team');
    }

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
