<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserTeams extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'user_team';
    protected $fillable = ['user_id', 'team_id', 'manager_id'];
}
