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

  

  
   
}
