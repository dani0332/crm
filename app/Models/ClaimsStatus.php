<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ClaimsStatus extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'claims_statuses';
}
