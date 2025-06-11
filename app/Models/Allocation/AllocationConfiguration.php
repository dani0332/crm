<?php

namespace App\Models\Allocation;

use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationConfiguration extends BaseMongoModel
{
    protected $fillable = [
        'quote_type_id',
        'quote_type',
        'lumpsum_brackets',
        'regular_brackets',
        'history',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'quote_type' => QuoteTypes::class,
        'lumpsum_brackets' => 'collection',
        'regular_brackets' => 'collection',
        'history' => 'collection',
    ];

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(QuoteType::class);
    }

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
}
