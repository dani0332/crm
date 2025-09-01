<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Class ClaimsLeadAllocationConfig
 *
 * Represents the configuration for claim lead allocation settings per user and quote type
 */
class ClaimsLeadAllocationConfig extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'claims_lead_allocation_config';

    protected $fillable = [
        'user_id',
        'quote_type_id',
        'max_capacity',
        'allocation_count',
        'auto_assignment_count',
        'manual_assignment_count',
        'last_allocated',
        'reset_cap',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
        'allocation_count' => 'integer',
        'auto_assignment_count' => 'integer',
        'manual_assignment_count' => 'integer',
        'last_allocated' => 'integer',
        'reset_cap' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this allocation config
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quote type for this allocation config
     */
    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    /**
     * Get the quote type enum value
     */
    public function getQuoteTypeEnum(): ?QuoteTypes
    {
        return QuoteTypes::getName($this->quote_type_id);
    }

    /**
     * Check if the user has reached their maximum capacity
     */
    public function hasReachedMaxCapacity(): bool
    {
        return $this->allocation_count >= $this->max_capacity;
    }

    /**
     * Get the remaining capacity
     */
    public function getRemainingCapacity(): int
    {
        return max(0, $this->max_capacity - $this->allocation_count);
    }

    /**
     * Increment allocation counters
     */
    public function incrementAllocationCounters(): bool
    {
        return $this->update([
            'allocation_count' => $this->allocation_count + 1,
            'auto_assignment_count' => $this->auto_assignment_count + 1,
            'last_allocated' => now()->timestamp,
        ]);
    }

    /**
     * Increment manual assignment counter
     */
    public function incrementManualAssignmentCount(): bool
    {
        return $this->update([
            'allocation_count' => $this->allocation_count + 1,
            'manual_assignment_count' => $this->manual_assignment_count + 1,
            'last_allocated' => now()->timestamp,
        ]);
    }

    /**
     * Reset allocation counters
     */
    public function resetCounters(): bool
    {
        return $this->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'reset_cap' => $this->reset_cap + 1,
        ]);
    }
}
