<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ClaimActivity extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'claim_activities';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'claim_request_id',
        'claim_uuid',
        'status_id',
        'comment',
        'comment_ai',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Define filterable fields for the FilterCriteria trait.
     */
    protected array $filterables = [
        'claim_request_id' => FilterTypes::EXACT,
        'claim_uuid' => FilterTypes::EXACT,
        'status_id' => FilterTypes::IN,
        'created_by_id' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($claimActivity) {
            if (! $claimActivity->created_by_id && Auth::check()) {
                $claimActivity->created_by_id = Auth::id();
            }
        });
    }

    /**
     * Get the claim request that owns this activity.
     */
    public function claimRequest(): BelongsTo
    {
        return $this->belongsTo(ClaimRequest::class, 'claim_request_id');
    }

    /**
     * Get the status associated with this activity.
     */
    public function claimStatus(): BelongsTo
    {
        return $this->belongsTo(ClaimStatus::class, 'status_id');
    }

    /**
     * Get the user who created this activity.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to filter activities by claim request.
     */
    public function scopeForClaimRequest(Builder $query, int $claimRequestId): Builder
    {
        return $query->where('claim_request_id', $claimRequestId);
    }

    /**
     * Scope to filter activities by claim UUID.
     */
    public function scopeForClaimUuid(Builder $query, string $claimUuid): Builder
    {
        return $query->where('claim_uuid', $claimUuid);
    }

    /**
     * Scope to filter activities by status.
     */
    public function scopeByStatus(Builder $query, array $statusIds): Builder
    {
        return $query->whereIn('status_id', $statusIds);
    }

    /**
     * Scope to filter activities by creator.
     */
    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by_id', $userId);
    }

    /**
     * Create a new activity for a claim request.
     */
    public static function createForClaim(
        int $claimRequestId,
        string $claimUuid,
        int $statusId,
        ?string $comment = null,
        ?string $commentAi = null,
        ?int $createdById = null
    ): self {
        return self::create([
            'claim_request_id' => $claimRequestId,
            'claim_uuid' => $claimUuid,
            'status_id' => $statusId,
            'comment' => $comment,
            'comment_ai' => $commentAi,
            'created_by_id' => $createdById ?? Auth::id(),
        ]);
    }
}
