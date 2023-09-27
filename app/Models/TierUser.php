<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TierUser extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'tier_users';
}
