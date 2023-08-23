<?php

namespace App\Models;

use App\Models\User;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Rule extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'rules';
    protected $fillable = ['name', 'rule_start_date', 'rule_end_date', 'is_active', 'rule_type'];


    /**
     * RELATIONS
     */

    /**
     * get rule details function
     *
     * @return HasOne
     */
    public function ruleDetail():HasMany
    {
        return $this->hasMany(
            RuleDetail::class,
            'rule_id',
            'id'
        );
    }

    /**
     * get rule users function
     *
     * @return BelongsToMany
     */
    public function ruleUsers():BelongsToMany
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
}
