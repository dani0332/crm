<?php

namespace App\Models\Allocation;

use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AllocationConfiguration extends BaseMongoModel implements AuditableContract
{
    use Auditable;

    protected $fillable = [
        'quote_type_id',
        'quote_type',
        'lumpsum_brackets',
        'regular_brackets',
        'history',
    ];
    protected $casts = [
        'quote_type' => QuoteTypes::class,
        'lumpsum_brackets' => 'array',
        'regular_brackets' => 'array',
        'history' => 'array',
    ];
    protected $auditInclude = [
        'quote_type_id',
        'quote_type',
        'lumpsum_brackets',
        'regular_brackets',
    ];

    /**
     * Generate audit tags
     */
    public function generateTags(): array
    {
        return ['allocation-configuration', 'config'];
    }

    /**
     * Relationship to QuoteType
     */
    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class);
    }

    /**
     * Get the history entries with user information
     */
    public function getHistoryWithUsersAttribute(): array
    {
        if (empty($this->history)) {
            return [];
        }

        return collect($this->history)->map(function ($entry) {
            if (isset($entry['user_id'])) {
                $user = User::find($entry['user_id']);
                $entry['user_name'] = $user?->name ?? 'Unknown User';
            }

            return $entry;
        })->toArray();
    }

    /**
     * Scope for filtering by quote type
     */
    public function scopeByQuoteType($query, string $quoteType)
    {
        return $query->where('quote_type', $quoteType);
    }

    /**
     * Scope for filtering by quote type ID
     */
    public function scopeByQuoteTypeId($query, int $quoteTypeId)
    {
        return $query->where('quote_type_id', $quoteTypeId);
    }
}
