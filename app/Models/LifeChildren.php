<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LifeChildren extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'life_children';
}
