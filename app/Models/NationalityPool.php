<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalityPool extends Model
{
    use SoftDeletes;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by')
            ->select('id', 'name');
    }
}
