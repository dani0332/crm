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

    public function ruleDetails()
    {
        return $this->hasMany(RuleDetail::class, 'lead_source_id');
    }
}
