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
use PDF;

class BuyLeadService
{
    private function currentRequestsCount(QuoteTypes $quoteType): int
    {
        return (int) BuyLeadRequest::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->notExpired()->sum('requested_count');
    }

    public function getBlLeadRemainingLimit(QuoteTypes $quoteType)
    {
        $leadAllocation = LeadAllocation::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->first();

        if (! $leadAllocation || ! $leadAllocation->buy_lead_status) {
            return 'DISABLED';
        }

        $buyLeadMaxCap = $leadAllocation?->buy_lead_max_capacity ?? 0;

        return $buyLeadMaxCap - $this->currentRequestsCount($quoteType);
    }

    public function isRequestAlreadySubmitted(QuoteTypes $quoteType): bool
    {
        return BuyLeadRequest::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->notExpired()->unfulfilled()->exists();
    }

    private function verifyPreChecks(RequestBuyLeadsRequest $request): ?string
    {
        $quoteType = $request->getQuoteType();
        $message = null;

        if (! auth()->user()->hasAnyRole($quoteType->advisorRoles())) {
            return 'You are not allowed to request buy leads for this quote type';
        }

        if ($this->isRequestAlreadySubmitted($quoteType)) {
            return 'You can initiate a new Buy Lead request once the existing requested leads are assigned.';
        }

        $remainingLimit = $this->getBlLeadRemainingLimit($quoteType);

        if ($remainingLimit === 'DISABLED' || $remainingLimit <= 0) {
            $message = 'You have reached your maximum buy leads allocation for today';
        } elseif ($request->count > $remainingLimit) {
            $leadStr = Str::plural('Lead', $remainingLimit);
            $isAre = $remainingLimit > 1 ? 'are' : 'is';

            $message = "You have exceeded your remaining buy leads allocation. Your remaining Buy {$leadStr} {$isAre} {$remainingLimit}";
        }

        return $message;
    }

    public function findConfig(QuoteTypes $quoteType): ?BuyLeadConfiguration
    {
        return BuyLeadConfiguration::where('quote_type_id', $quoteType->id())->where('department_id', Auth::user()->department_id ?? 0)->first();
    }

    public function findConfigCost(QuoteTypes $quoteType)
    {
        $config = $this->findConfig($quoteType);
        if (! $config) {
            return 'No configuration found for this quote type';
        }

        $cost = null;
        $requestType = null;

        if (Auth::user()->isValueUser($quoteType)) {
            $cost = $config->value;
            $requestType = 'value';
        } elseif (Auth::user()->isVolumeUser($quoteType)) {
            $cost = $config->volume;
            $requestType = 'volume';
        }

        if (is_null($cost)) {
            return "You're neither a value user nor a volume user";
        }

        if ($cost <= 0) {
            return 'System is unable to process your request.';
        }

        return [$cost, $requestType, $config->segment];
    }

    public function requestBuyLeads(RequestBuyLeadsRequest $request)
    {
        if ($message = $this->verifyPreChecks($request)) {
            return $message;
        }

        $configCost = $this->findConfigCost($request->getQuoteType());
        if (is_string($configCost)) {
            return $configCost;
        }

        [$cost, $requestType, $segment] = $configCost;

        BuyLeadRequest::create([
            'quote_type_id' => $request->getQuoteTypeId(),
            'user_id' => Auth::id(),
            'requested_count' => $request->count,
            'cost_per_lead' => $cost,
            'request_type' => $requestType,
            'expires_at' => now()->endOfDay(),
            'department_id' => Auth::user()->department_id,
            'segment' => $segment,
        ]);

        return null;
    }

    public function getTodaysRequests()
    {
        return BuyLeadRequest::select('id', 'quote_type_id', 'requested_count', 'allocated_count', 'cost_per_lead', 'created_at')
            ->selectRaw('CONCAT(ROUND(requested_count * cost_per_lead, 0), " AED") as total_cost')
            ->with('quoteType:id,code')
            ->where('user_id', Auth::id())
            ->latest()
            ->whereDate('created_at', today())
            ->simplePaginate(20)
            ->withQueryString();
    }

    public function getTrackingData(QuoteTypes $quoteType, Carbon $startDate, Carbon $endDate, bool $isExport = false)
    {
        return BuyLeadRequestLog::select('buy_lead_request_logs.id', 'buy_lead_request_logs.quote_type_id', 'buy_lead_request_logs.uuid as ref_id', 'buy_lead_requests.created_at', 'departments.name as department')
            ->selectRaw('CONCAT(ROUND(buy_lead_requests.cost_per_lead, 0), " AED") as cost')
            ->with('quoteType:id,code')
            ->join('buy_lead_requests', 'buy_lead_requests.id', '=', 'buy_lead_request_logs.buy_lead_request_id')
            ->join('users', 'users.id', '=', 'buy_lead_requests.user_id')
            ->leftJoin('departments', 'buy_lead_requests.department_id', '=', 'departments.id')
            ->where('buy_lead_requests.user_id', Auth::id())
            ->where('buy_lead_request_logs.quote_type_id', $quoteType->id())
            ->whereBetween('buy_lead_request_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->latest('buy_lead_request_logs.created_at')
            ->when($isExport,
                fn ($q) => $q->get(),
                fn ($q) => $q->simplePaginate(20)->withQueryString(),
            );

    }

    public function exportTrackingReportPDF(QuoteTypes $quoteType, Carbon $startDate, Carbon $endDate)
    {
        $data['list'] = $this->getTrackingData($quoteType, $startDate, $endDate, true);
        $data['quoteType'] = $quoteType;
        $pdf = PDF::loadView('pdf.buy-lead-requests', $data);

        $pdfName = 'InsuranceMarket.ae™ Buy Leads Tracking Report.pdf';

        return $pdf->download($pdfName);
    }
}
