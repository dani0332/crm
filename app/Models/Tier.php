<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Tier extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'tiers';
    protected $fillable = ['name', 'min_price', 'max_price', 'is_tpl', 'is_active', 'cost_per_lead', 'is_auto_assignment_enabled'];
}
