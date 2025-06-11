<?php

namespace App\Models\Allocation;

use App\Enums\QuoteTypes;
use App\Models\BaseMongoModel;

class AllocationConfiguration extends BaseMongoModel
{
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
}
