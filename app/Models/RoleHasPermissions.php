<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SpatieActivityLog;

class RoleHasPermissions
{
    use HasFactory, SpatieActivityLog;

    protected $table = 'role_has_permissions';
}
