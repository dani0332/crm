<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalityPool extends Model
{
    protected $table = 'nationality_pool';
    protected $fillable = [
        'effective_from',
        'effective_to',
        'canonical_nationality_codes',
        'health_nationality_group_ids',
        'is_active',
        'logged_by',
    ];
    protected $attributes = [
        'is_active' => true,
    ];
    protected $casts = [
        'effective_from' => 'date:d/m/Y',
        'effective_to' => 'date:d/m/Y',
        'created_at' => 'date:d/m/Y',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by')
            ->select('id', 'name');
    }
}
