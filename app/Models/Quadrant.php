<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Quadrant extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'quadrants';
    protected $fillable = ['name', 'is_active', 'code'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'quad_users', 'quad_id', 'user_id');
    }

    public function tiers()
    {
        return $this->belongsToMany(Tier::class, 'quad_tiers', 'quad_id', 'tier_id');
    }
}
