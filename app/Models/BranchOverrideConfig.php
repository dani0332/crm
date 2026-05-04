<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Branch Override Configuration Model
 *
 * Manages branch override configurations from
 * a source branch to a target branch for specific quote types.
 */
class BranchOverrideConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branch_override_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_branch_id',
        'target_branch_id',
        'quote_type_id',
        'start_date',
        'end_date',
        'override_text',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'source_branch_id' => 'integer',
        'target_branch_id' => 'integer',
        'quote_type_id' => 'integer',
    ];

    /**
     * Get the source branch that owns the override config.
     */
    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    /**
     * Get the target branch that owns the override config.
     */
    public function targetBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    /**
     * Get the quote type that owns the override config.
     */
    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    /**
     * Scope a query to only include active overrides.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Check if the override is currently active.
     */
    public function isActive(): bool
    {
        return $this->start_date->isPast() &&
            ($this->end_date === null || $this->end_date->isFuture());
    }
}
