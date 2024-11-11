<?php

namespace App\Models;

use App\Enums\QuadrantCodeEnum;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Tier extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    protected $table = 'tiers';
    protected $guarded = [];

    /**
     * @return $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tier_users', 'tier_id', 'user_id');
    }

    public function quadrants()
    {
        return $this->belongsToMany(Quadrant::class, 'quad_tiers', 'tier_id', 'quad_id');
    }

    public function isValue()
    {
        return $this->quadrants()->where('code', QuadrantCodeEnum::VALUE)->exists();
    }

    public function isVolume()
    {
        return $this->quadrants()->where('code', QuadrantCodeEnum::VOLUME)->exists();
    }
}
