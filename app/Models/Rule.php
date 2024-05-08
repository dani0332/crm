<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Rule extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'rules';
    protected $fillable = ['name', 'rule_start_date', 'rule_end_date', 'is_active', 'rule_type'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'rule_users');
    }

    public function leadSources()
    {
        return $this->hasMany(RuleLeadSource::class, 'rule_id');
    }

    /**
     * get rule details function
     */
    public function ruleDetail(): hasOne
    {
        return $this->hasOne(
            RuleDetail::class,
            'rule_id',
            'id'
        );
    }

    /**
     * get rule users function
     */
    public function ruleUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'rule_users',
            'rule_id',
            'user_id',
            'id',
            'id',
            'ruleUsers'
        )->withTimestamps();
    }

    /**
     * get rule type function
     */
    public function ruleType(): BelongsTo
    {
        return $this->belongsTo(
            RuleType::class,
            'rule_type',
            'id',
            'ruleType'
        );
    }

    public function leadSource()
    {
        return $this->hasOneThrough(
            LeadSource::class,
            RuleDetail::class,
            'rule_id', // Foreign key on the RuleDetail table
            'id', // Local key on the LeadSource table
            'id', // Local key on the Rule table
            'lead_source_id' // Foreign key on the LeadSource table
        );
    }
}
