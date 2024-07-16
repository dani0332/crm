<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentTeams extends Model
{
    protected $table = 'department_teams';
    protected $fillable = ['team_id', 'department_id'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
