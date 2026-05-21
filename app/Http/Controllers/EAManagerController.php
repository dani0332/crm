<?php

namespace App\Http\Controllers;

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\RolesEnum;
use App\Jobs\SendEAManagerDecisionEmailJob;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\EAManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EAManagerController extends Controller
{
    public function __construct(private readonly EAManagerService $service)
    {
        $this->middleware(fn ($request, $next) => auth()->user()->hasRole(RolesEnum::EAManager)
            ? $next($request)
            : abort(403));
    }

    public function index(Request $request)
    {
        $filters = $request->only(['ref_id', 'lob', 'status', 'ea_model', 'date_from', 'date_to']);

        $carLeads = $this->queryLeads(CarQuote::class, $filters, 'car');
        $healthLeads = $this->queryLeads(HealthQuote::class, $filters, 'health');
        $personalLeads = $this->queryPersonalLeads($filters);

        $leads = $carLeads->concat($healthLeads)->concat($personalLeads)
            ->sortByDesc('created_at')
            ->values();

        return inertia('EAManager/Index', [
            'leads' => $leads,
            'filters' => $filters,
            'pendingRejectionsCount' => $this->service->pendingRejectionsCount(),
        ]);
    }

    public function pendingRejectionsCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->service->pendingRejectionsCount(),
        ]);
    }

    public function approve(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $quote = $this->resolveQuote($quoteType, $quoteId);

        $quote->ea_assigned_advisor_approved_at = now();
        $quote->ea_expert_advisor_approved_at = now();
        $quote->save();

        SendEAManagerDecisionEmailJob::dispatch($quote, $quoteType, 'Approved');

        return response()->json(['success' => true]);
    }

    public function changeModel(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $request->validate(['ea_model' => 'required|in:referral,collaborate']);

        $quote = $this->resolveQuote($quoteType, $quoteId);
        $esModel = EaModelEnum::from($request->ea_model);

        if ($esModel === EaModelEnum::Referral && $quote->ea_model === EaModelEnum::Collaborate) {
            // Convert collaborate → referral:
            // expert advisor becomes assigned advisor; old assigned becomes lead generator
            $quote->lead_generator_id = $quote->advisor_id;
            $quote->advisor_id = $quote->expert_advisor_id;
            $quote->expert_advisor_id = null;
            $quote->ea_model = EaModelEnum::Referral;
            $quote->ea_assigned_advisor_approved_at = null;
            $quote->ea_expert_advisor_approved_at = null;
            $quote->ea_assigned_advisor_rejected_at = null;
            $quote->ea_expert_advisor_rejected_at = null;
            $quote->save();
        }

        SendEAManagerDecisionEmailJob::dispatch($quote, $quoteType, 'Model Changed to Referral');

        return response()->json(['success' => true]);
    }

    /** @param class-string $modelClass */
    private function queryLeads(string $modelClass, array $filters, string $quoteType): Collection
    {
        $query = $modelClass::with(['advisor:id,name,email', 'expertAdvisor:id,name', 'leadGenerator:id,name'])
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->when($filters['ref_id'] ?? null, fn ($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('quote_status_id', $v))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->get();

        return $query->map(fn ($lead) => $this->formatLead($lead, $quoteType));
    }

    private function queryPersonalLeads(array $filters): Collection
    {
        $query = PersonalQuote::with(['advisor:id,name,email', 'expertAdvisor:id,name', 'leadGenerator:id,name'])
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->when($filters['ref_id'] ?? null, fn ($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($filters['lob'] ?? null, fn ($q, $v) => $q->where('quote_type_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('quote_status_id', $v))
            ->when($filters['ea_model'] ?? null, fn ($q, $v) => $q->where('ea_model', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->get();

        return $query->map(fn ($lead) => $this->formatLead($lead, 'personal'));
    }

    private function formatLead($lead, string $quoteType): array
    {
        return [
            'id' => $lead->id,
            'code' => $lead->code,
            'quote_type' => $quoteType,
            'ea_model' => $lead->ea_model,
            'quote_status_id' => $lead->quote_status_id,
            'created_at' => $lead->created_at,
            'advisor' => optional($lead->advisor)->only(['id', 'name', 'email']),
            'expert_advisor' => optional($lead->expertAdvisor)->only(['id', 'name']),
            'lead_generator' => optional($lead->leadGenerator)->only(['id', 'name']),
            'ea_assigned_advisor_rejected_at' => $lead->ea_assigned_advisor_rejected_at,
            'ea_expert_advisor_rejected_at' => $lead->ea_expert_advisor_rejected_at,
        ];
    }

    private function resolveQuote(string $quoteType, int $quoteId): CarQuote|HealthQuote|PersonalQuote
    {
        return match ($quoteType) {
            'car' => CarQuote::findOrFail($quoteId),
            'health' => HealthQuote::findOrFail($quoteId),
            default => PersonalQuote::findOrFail($quoteId),
        };
    }
}
