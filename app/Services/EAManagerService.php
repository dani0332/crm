<?php

namespace App\Services;

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EAManagerService
{
    public function getLeads(array $filters = []): Collection
    {
        $lob = isset($filters['lob']) && $filters['lob'] !== '' ? (int) $filters['lob'] : null;

        // Only query the model that owns this LOB; when no LOB filter is set, query all three.
        $carLeads = ($lob === null || $lob === QuoteTypeId::Car)
            ? $this->queryModel(CarQuote::class, $filters, 'car')
            : collect();

        $healthLeads = ($lob === null || $lob === QuoteTypeId::Health)
            ? $this->queryModel(HealthQuote::class, $filters, 'health')
            : collect();

        $personalLeads = ($lob === null || ! in_array($lob, [QuoteTypeId::Car, QuoteTypeId::Health], true))
            ? $this->queryPersonalLeads($filters)
            : collect();

        return $carLeads->concat($healthLeads)->concat($personalLeads)
            ->sortByDesc('created_at')
            ->values();
    }

    /** @param class-string $modelClass */
    private function queryModel(string $modelClass, array $filters, string $quoteType): Collection
    {
        return $modelClass::with(['advisor:id,name,email', 'expertAdvisor:id,name', 'leadGenerator:id,name'])
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->when($filters['ref_id'] ?? null, fn ($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $this->applyEaStatusFilter($q, $v))
            ->when($filters['lead_generator'] ?? null, fn ($q, $v) => $q->whereHas('leadGenerator', fn ($uq) => $uq->where('name', 'like', "%{$v}%")))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, $quoteType));
    }

    private function queryPersonalLeads(array $filters): Collection
    {
        return PersonalQuote::with(['advisor:id,name,email', 'expertAdvisor:id,name', 'leadGenerator:id,name'])
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->when($filters['ref_id'] ?? null, fn ($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($filters['lob'] ?? null, fn ($q, $v) => $q->where('quote_type_id', $v))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $this->applyEaStatusFilter($q, $v))
            ->when($filters['lead_generator'] ?? null, fn ($q, $v) => $q->whereHas('leadGenerator', fn ($uq) => $uq->where('name', 'like', "%{$v}%")))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, QuoteTypes::getName($lead->quote_type_id)?->value ?? 'personal'));
    }

    private function computeEaStatus(CarQuote|HealthQuote|PersonalQuote $lead): string
    {
        // Approved only when BOTH advisors have approved (FRD §D: lead progresses only if both approve)
        if ($lead->ea_assigned_advisor_approved_at && $lead->ea_expert_advisor_approved_at) {
            return 'approved';
        }
        if ($lead->ea_assigned_advisor_rejected_at || $lead->ea_expert_advisor_rejected_at) {
            return 'rejected';
        }

        return 'pending';
    }

    private function applyEaStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            // Both timestamps required — consistent with dual-approval FRD requirement
            'approved' => $query->whereNotNull('ea_assigned_advisor_approved_at')->whereNotNull('ea_expert_advisor_approved_at'),
            'rejected' => $query->where(fn ($q) => $q->whereNotNull('ea_assigned_advisor_rejected_at')->orWhereNotNull('ea_expert_advisor_rejected_at')),
            // Pending = not fully approved and not rejected (includes partial-approval intermediary state)
            'pending' => $query->where(fn ($q) => $q
                ->where(fn ($inner) => $inner->whereNull('ea_assigned_advisor_approved_at')->orWhereNull('ea_expert_advisor_approved_at'))
                ->whereNull('ea_assigned_advisor_rejected_at')
                ->whereNull('ea_expert_advisor_rejected_at')
            ),
            default => null,
        };
    }

    private function formatLead($lead, string $quoteType): array
    {
        return [
            'id' => $lead->id,
            'code' => $lead->code,
            'quote_type' => $quoteType,
            'ea_model' => EaModelEnum::tryFrom($lead->getRawOriginal('ea_model'))?->value,
            'quote_status_id' => $lead->quote_status_id,
            'created_at' => $lead->created_at,
            'advisor' => optional($lead->advisor)->only(['id', 'name', 'email']),
            'expert_advisor' => optional($lead->expertAdvisor)->only(['id', 'name']),
            'lead_generator' => optional($lead->leadGenerator)->only(['id', 'name']),
            'ea_assigned_advisor_rejected_at' => $lead->ea_assigned_advisor_rejected_at,
            'ea_expert_advisor_rejected_at' => $lead->ea_expert_advisor_rejected_at,
            'has_rejection' => (bool) ($lead->ea_assigned_advisor_rejected_at || $lead->ea_expert_advisor_rejected_at),
            'ea_status' => $this->computeEaStatus($lead),
        ];
    }

    public function pendingRejectionsCount(): int
    {
        $rejected = fn ($q) => $q->whereNotNull('ea_assigned_advisor_rejected_at')
            ->orWhereNotNull('ea_expert_advisor_rejected_at');

        return CarQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count()
            + HealthQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count()
            + PersonalQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($rejected)->count();
    }
}
