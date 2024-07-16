<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Department extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'departments';
    protected $fillable = ['name', 'status', 'created_at', 'updated_at'];

    public function departmentTeams()
    {
        return $this->hasMany(DepartmentTeams::class, 'department_id', 'id')->with('team');
    }
}
