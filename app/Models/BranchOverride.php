<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Branch Override Model
 *
 * Tracks individual instances where a quote was overridden from
 * one branch to another based on branch override configurations.
 *
 */
class BranchOverride extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branch_overrides';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_override_config_id',
        'quotable_type',
        'quotable_id',
    ];

    /**
     * Get the parent quotable model (CarQuote, TravelQuote, etc.).
     */
    public function quotable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the branch override config that owns this override.
     */
    public function branchOverrideConfig(): BelongsTo
    {
        return $this->belongsTo(BranchOverrideConfig::class, 'branch_override_config_id');
    }

}

