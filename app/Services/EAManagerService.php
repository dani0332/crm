<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Support\Collection;

class EAManagerService
{
    public function getLeads(array $filters = []): Collection
    {
        $carLeads = $this->queryModel(CarQuote::class, $filters, 'car');
        $healthLeads = $this->queryModel(HealthQuote::class, $filters, 'health');
        $personalLeads = $this->queryPersonalLeads($filters);

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
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('quote_status_id', $v))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
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
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('quote_status_id', $v))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, 'personal'));
    }

    private function formatLead($lead, string $quoteType): array
    {
        return [
            'id' => $lead->id,
            'code' => $lead->code,
            'quote_type' => $quoteType,
            'ea_model' => $lead->ea_model?->value,
            'quote_status_id' => $lead->quote_status_id,
            'created_at' => $lead->created_at,
            'advisor' => optional($lead->advisor)->only(['id', 'name', 'email']),
            'expert_advisor' => optional($lead->expertAdvisor)->only(['id', 'name']),
            'lead_generator' => optional($lead->leadGenerator)->only(['id', 'name']),
            'ea_assigned_advisor_rejected_at' => $lead->ea_assigned_advisor_rejected_at,
            'ea_expert_advisor_rejected_at' => $lead->ea_expert_advisor_rejected_at,
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
