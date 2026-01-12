<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Branch extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'branches';
    protected $fillable = ['code', 'name', 'type', 'status'];

    public function userBranches()
    {
        return $this->hasMany(UserBranch::class, 'branch_id', 'id');
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }

    public function scopeActive($query)
    {
        $query->where('status', 1);
    }
}
