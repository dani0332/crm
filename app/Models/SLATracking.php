<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteTypes;
use App\Enums\SLAStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SLATracking extends Model
{
    protected $table = 'sla_trackings';
    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'advisor_id',
        'assigned_at',
        'sla_due_at',
        'reminder_sent_at',
        'met_at',
        'breached_at',
        'status',
        'reason',
        'breach_escalated_at',
        'is_assigned_during_business_hours',
        'next_business_day_start',
    ];
    protected $casts = [
        'assigned_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'met_at' => 'datetime',
        'breached_at' => 'datetime',
        'breach_escalated_at' => 'datetime',
        'next_business_day_start' => 'datetime',
        'is_assigned_during_business_hours' => 'boolean',
        'status' => SLAStatusEnum::class,
    ];

    public function trackable()
    {
        return $this->morphTo();
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function scopeActive($query)
    {
        $query->where('status', SLAStatusEnum::ACTIVE);
    }

    public function scopeDueForReminder($query, int $reminderMinutes)
    {
        $threshold = now()->addMinutes($reminderMinutes);

        return $query->active()
            ->whereNull('reminder_sent_at')
            ->where('sla_due_at', '<=', $threshold);
    }

    public function scopeDueForBreach($query)
    {
        return $query->active()
            ->where('sla_due_at', '<', now())
            ->whereNull('breach_escalated_at');
    }

    public function markMet(): void
    {
        $this->update([
            'status' => SLAStatusEnum::MET,
            'reason' => 'SLA met - callback made within required timeframe',
            'met_at' => now(),
        ]);
    }

    public function markBreached(?string $reason = null): void
    {
        $this->update([
            'status' => SLAStatusEnum::BREACHED,
            'reason' => $reason ?? 'SLA breach - callback not made within required timeframe',
            'breached_at' => now(),
            'breach_escalated_at' => now(),
        ]);
    }

    public function markCanceled(?string $reason = null): void
    {
        $this->update([
            'status' => SLAStatusEnum::CANCELED,
            'reason' => $reason ?? 'SLA tracking canceled as lead is no longer PEC-marked',
        ]);
    }

    public function markReassigned(?string $reason = null): void
    {
        $this->update([
            'status' => SLAStatusEnum::REASSIGNED,
            'reason' => $reason ?? 'Lead reassigned to another advisor',
        ]);
    }

    public function getLead()
    {
        return $this->trackable;
    }

    public function getQuoteType(): QuoteTypes
    {
        return match ($this->trackable_type) {
            HealthQuote::class => QuoteTypes::HEALTH,
            default => 'unknown',
        };
    }

    public function getLeadUuid(): ?string
    {
        return $this->trackable?->uuid;
    }

    public function scopeByLead($query, Model $lead)
    {
        $query->where('trackable_type', $lead->getMorphClass())->where('trackable_id', $lead->id);
    }
}
