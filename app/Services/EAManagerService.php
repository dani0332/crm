<?php

namespace App\Services;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EAManagerService
{
    public function getPendingRejections(array $filters = []): Collection
    {
        return $this->getLeads([...$filters, 'status' => 'rejected']);
    }

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

        $businessLeads = ($lob === null || in_array($lob, [QuoteTypeId::Corpline, QuoteTypeId::GroupMedical], true))
            ? $this->queryModel(BusinessQuote::class, $filters, 'business')
            : collect();

        $personalLeads = ($lob === null || ! in_array($lob, [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical], true))
            ? $this->queryPersonalLeads($filters)
            : collect();

        return $carLeads->concat($healthLeads)->concat($businessLeads)->concat($personalLeads)
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

    private function computeEaStatus(CarQuote|HealthQuote|PersonalQuote|BusinessQuote $lead): ?string
    {
        if ($lead->ea_manager_approved_at) {
            return 'approved';
        }
        if ($lead->ea_manager_rejected_at) {
            return 'rejected';
        }
        if ($lead->ea_assigned_advisor_approved_at && $lead->ea_expert_advisor_approved_at) {
            return 'approved';
        }
        if ($lead->ea_assigned_advisor_rejected_at || $lead->ea_expert_advisor_rejected_at) {
            return 'rejected';
        }

        return null;
    }

    private function applyEaStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'approved' => $query->where(fn ($q) => $q
                ->whereNotNull('ea_manager_approved_at')
                ->orWhere(fn ($inner) => $inner
                    ->whereNotNull('ea_assigned_advisor_approved_at')
                    ->whereNotNull('ea_expert_advisor_approved_at')
                )
            ),
            'rejected' => $query->where(fn ($q) => $q
                ->whereNotNull('ea_assigned_advisor_rejected_at')
                ->orWhereNotNull('ea_expert_advisor_rejected_at')
            )->whereNull('ea_manager_approved_at')->whereNull('ea_manager_rejected_at'),
            default => null,
        };
    }

    private function formatLead($lead, string $quoteType): array
    {
        return [
            'id' => $lead->id,
            'code' => $lead->code,
            'quote_type' => $quoteType === 'business'
                ? ($lead->business_type_of_insurance_id === BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL ? 'groupmedical' : 'corpline')
                : $quoteType,
            'ea_model' => EaModelEnum::tryFrom($lead->getRawOriginal('ea_model'))?->value,
            'quote_status_id' => $lead->quote_status_id,
            'created_at' => $lead->created_at,
            'advisor' => optional($lead->advisor)->only(['id', 'name', 'email']),
            'expert_advisor' => optional($lead->expertAdvisor)->only(['id', 'name']),
            'lead_generator' => optional($lead->leadGenerator)->only(['id', 'name']),
            'ea_assigned_advisor_rejected_at' => $lead->ea_assigned_advisor_rejected_at,
            'ea_expert_advisor_rejected_at' => $lead->ea_expert_advisor_rejected_at,
            'has_rejection' => (bool) ($lead->ea_assigned_advisor_rejected_at || $lead->ea_expert_advisor_rejected_at),
            'ea_manager_id' => $lead->ea_manager_id,
            'ea_manager_approved_at' => $lead->ea_manager_approved_at,
            'ea_manager_rejected_at' => $lead->ea_manager_rejected_at,
            'ea_status' => $this->computeEaStatus($lead),
        ];
    }

    public function pendingRejectionsCount(): int
    {
        // Exact condition per lead: advisor rejected AND manager has not yet approved or rejected
        $pending = fn ($q) => $q
            ->where(fn ($inner) => $inner
                ->whereNotNull('ea_assigned_advisor_rejected_at')
                ->orWhereNotNull('ea_expert_advisor_rejected_at')
            )
            ->whereNull('ea_manager_approved_at')
            ->whereNull('ea_manager_rejected_at');

        return CarQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($pending)->count()
            + HealthQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($pending)->count()
            + BusinessQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($pending)->count()
            + PersonalQuote::where('source', LeadSourceEnum::EA_IMCRM)->where($pending)->count();
    }
}
