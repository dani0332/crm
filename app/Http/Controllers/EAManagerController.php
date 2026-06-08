<?php

namespace App\Http\Controllers;

use App\Enums\EaModelEnum;
use App\Enums\RolesEnum;
use App\Exports\EAManagerExport;
use App\Jobs\SendEAManagerDecisionEmailJob;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\EAManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $filters = $request->only(['ref_id', 'lob', 'status', 'ea_model', 'date_from', 'date_to', 'lead_generator']);

        return inertia('EAManager/Index', [
            'leads' => $this->service->getLeads($filters),
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

    public function decision(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        $isApprove = $request->action === 'approve';
        $quote = $this->resolveQuote($quoteType, $quoteId);

        if ($isApprove) {
            $quote->ea_assigned_advisor_approved_at = now();
            $quote->ea_expert_advisor_approved_at = now();
            $quote->ea_assigned_advisor_rejected_at = null;
            $quote->ea_expert_advisor_rejected_at = null;
            $quote->save();

            SendEAManagerDecisionEmailJob::dispatch($quote, $quoteType, 'Approved');
        } else {
            $this->demoteToReferral($quote);

            SendEAManagerDecisionEmailJob::dispatch($quote, $quoteType, 'Rejected');
        }

        return response()->json(['success' => true]);
    }

    public function changeModel(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $request->validate(['ea_model' => 'required|in:referral,collaborate']);

        $quote = $this->resolveQuote($quoteType, $quoteId);
        $esModel = EaModelEnum::from($request->ea_model);

        if ($esModel !== EaModelEnum::Referral || $quote->ea_model !== EaModelEnum::Collaborate) {
            return response()->json(['success' => true]);
        }

        $this->demoteToReferral($quote);

        SendEAManagerDecisionEmailJob::dispatch($quote, $quoteType, 'Model Changed to Referral');

        return response()->json(['success' => true]);
    }

    private function demoteToReferral(CarQuote|HealthQuote|PersonalQuote $quote): void
    {
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

    private function resolveQuote(string $quoteType, int $quoteId): CarQuote|HealthQuote|PersonalQuote
    {
        return match ($quoteType) {
            'car' => CarQuote::findOrFail($quoteId),
            'health' => HealthQuote::findOrFail($quoteId),
            default => PersonalQuote::findOrFail($quoteId),
        };
    }

    public function export(Request $request): StreamedResponse
    {
        return app(EAManagerExport::class)->download('EA-Manager-Leads');
    }

}
