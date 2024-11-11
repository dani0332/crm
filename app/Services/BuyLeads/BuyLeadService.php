<?php

namespace App\Services\BuyLeads;

use App\Enums\QuoteTypes;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadConfiguration;
use App\Models\BuyLeadRequest;
use App\Models\BuyLeadRequestLog;
use App\Models\LeadAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BuyLeadService
{
    private function currentRequestsCount(QuoteTypes $quoteType): int
    {
        return (int) BuyLeadRequest::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->active()->sum('requested_count');
    }

    public function getBlLeadRemainingLimit(QuoteTypes $quoteType)
    {
        $leadAllocation = LeadAllocation::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->first();

        $buyLeadMaxCap = $leadAllocation?->buy_lead_max_capacity ?? 0;

        return $buyLeadMaxCap - $this->currentRequestsCount($quoteType);
    }

    private function verifyPreChecks(RequestBuyLeadsRequest $request): ?string
    {
        $quoteType = $request->getQuoteType();
        $message = null;

        if (! auth()->user()->hasAnyRole($quoteType->advisorRoles())) {
            return 'You are not allowed to request buy leads for this quote type';
        }

        $remainingLimit = $this->getBlLeadRemainingLimit($quoteType);

        if ($remainingLimit <= 0) {
            $message = 'You have reached your maximum buy leads allocation for today';
        } elseif ($request->count > $remainingLimit) {
            $leadStr = Str::plural('Lead', $remainingLimit);
            $isAre = $remainingLimit > 1 ? 'are' : 'is';

            $message = "You have exceeded your remaining buy leads allocation. Your remaining Buy {$leadStr} {$isAre} {$remainingLimit}";
        }

        return $message;
    }

    private function findConfig(RequestBuyLeadsRequest $request): ?BuyLeadConfiguration
    {
        $userDepartmentIds = [
            auth()->user()->department_id ?? 0,
            ...auth()->user()->departments->pluck('id')->toArray(),
        ];

        return BuyLeadConfiguration::where('quote_type_id', $request->getQuoteTypeId())->whereIn('department_id', $userDepartmentIds)->first();
    }

    public function requestBuyLeads(RequestBuyLeadsRequest $request)
    {
        if ($message = $this->verifyPreChecks($request)) {
            return response()->json(['message' => $message], 422);
        }

        $config = $this->findConfig($request);
        if (! $config) {
            return response()->json(['message' => 'No Buy Lead configuration found for this quote type'], 404);
        }
        $buyLeadRequest = BuyLeadRequest::create([
            'quote_type_id' => $request->getQuoteTypeId(),
            'user_id' => Auth::id(),
            'requested_count' => $request->count,
            'value_cost_per_lead' => $config->value,
            'volume_cost_per_lead' => $config->volume,
            'expires_at' => now()->endOfDay(),
        ]);

        return response()->json(['message' => 'Buy Lead Request created successfully', 'buy_lead_request_id' => $buyLeadRequest->id], 201);
    }

    public function getTodaysRequests()
    {
        return BuyLeadRequest::with('quoteType:id,code')->where('user_id', Auth::id())->latest()->whereDate('created_at', today())->simplePaginate(20)->withQueryString();
    }

    public function getTrackingData(QuoteTypes $quoteType, string $date)
    {
        return BuyLeadRequestLog::select('buy_lead_request_logs.id', 'buy_lead_request_logs.quote_type_id', 'buy_lead_request_logs.uuid as ref_id', 'buy_lead_request_logs.cost_per_lead as cost', 'buy_lead_requests.created_at as requested_date', 'departments.name as department')
            ->with('quoteType:id,code')
            ->join('buy_lead_requests', 'buy_lead_requests.id', '=', 'buy_lead_request_logs.buy_lead_request_id')
            ->join('users', 'users.id', '=', 'buy_lead_requests.user_id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->where('buy_lead_requests.user_id', Auth::id())
            ->where('buy_lead_request_logs.quote_type_id', $quoteType->id())
            ->whereDate('buy_lead_request_logs.created_at', Carbon::parse($date))
            ->latest('buy_lead_request_logs.created_at')
            ->simplePaginate(20)
            ->withQueryString();
    }
}
