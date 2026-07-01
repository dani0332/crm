<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessActivity extends Model
{
    use HasFactory;

    public function scopeActive($query)
    {
        $query->where('status', 1);
    }

    public function quoteTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            QuoteType::class,
            'business_activity_quote_type_mapping',
            'business_activity_id',
            'quote_type_id'
        );
    }
}
