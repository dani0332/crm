<?php

namespace App\Models;

use App\Traits\Optionable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeadSource extends Model implements AuditableContract
{
    use Auditable, HasFactory, Optionable;

    protected $table = 'lead_sources';
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_applicable_for_rules',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'is_applicable_for_rules' => 'boolean',
    ];

    public function ruleDetails()
    {
        return $this->hasMany(RuleDetail::class, 'lead_source_id');
    }

    #[Scope]
    public function withActive($query)
    {
        return $query->where('is_active', 1);
    }

    #[Scope]
    public function applicableForRules($query)
    {
        return $query->where('is_applicable_for_rules', true);
    }
}
