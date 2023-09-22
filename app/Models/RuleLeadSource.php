<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RuleLeadSource extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'rule_lead_sources';
    protected $fillable = ['lead_source_id', 'rule_id', 'user_id', 'created_at', 'updated_at'];
}
