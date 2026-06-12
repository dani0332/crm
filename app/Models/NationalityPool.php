<?php

namespace App\Models;

use App\Events\NationalityPoolCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NationalityPool extends Model
{
    use HasFactory, SoftDeletes;

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

    protected static function booted(): void
    {
        static::created(function (NationalityPool $pool): void {
            event(new NationalityPoolCreated($pool));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by')
            ->select('id', 'name');
    }
}
