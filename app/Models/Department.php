<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Department extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'departments';
    protected $fillable = ['name', 'is_active'];

    public function teams()
    {
        return $this->hasMany(DepartmentTeams::class, 'department_id', 'id')->with('team');
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
