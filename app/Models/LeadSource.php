<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeadSource extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'lead_sources';
    protected $fillable = [
        'name',
        'is_active',
        'is_applicable_for_rules',
    ];

    public function ruleDetails()
    {
        return $this->hasMany(RuleDetail::class, 'lead_source_id');
    }
}
