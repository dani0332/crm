<?php

namespace App\Services\BuyLeads;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadConfiguration;
use App\Models\BuyLeadConfigurationNationality;
use App\Models\BuyLeadRequest;
use App\Models\BuyLeadRequestLog;
use App\Models\LeadAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PDF;

class BuyLeadService
{
    public function getBlLeadRemainingLimit(QuoteTypes $quoteType, bool $isCarRevival = false)
    {
        $isCarRevival = $isCarRevival || $quoteType->value == QuoteTypes::CAR_CAT_A->value;
        // if the quote type is car revival, then set the quote type to car
        if ($isCarRevival) {
            $quoteType = QuoteTypes::CAR;
            // check if the user has the car revival advisor role
        }
        $leadAllocation = LeadAllocation::where('quote_type_id', $quoteType->id())->where('user_id', Auth::id())->first();
        if (! $leadAllocation || ! $leadAllocation->buy_lead_status) {
            return 'DISABLED';
        }

        $buyLeadMaxCap = $leadAllocation?->buy_lead_max_capacity ?? 0;

        $assignedCount = $isCarRevival ? $leadAllocation->buy_lead_cat_a_allocation_count : $leadAllocation->buy_lead_allocation_count;

        return $buyLeadMaxCap - $assignedCount;
    }

    public function isRequestAlreadySubmitted(QuoteTypes $quoteType, bool $isCarRevival = false): bool
    {
        return BuyLeadRequest::where('quote_type_id', $quoteType->id())
            ->where('user_id', Auth::id())
            ->notExpired()
            ->unfulfilled()
            ->when($isCarRevival, fn ($query) => $query->catA(), fn ($query) => $query->nonCatA())
            ->exists();
    }

    private function verifyPreChecks(RequestBuyLeadsRequest $request): ?string
    {

        $quoteType = $request->getQuoteType();
        $isCarRevival = $request->getQuoteType()->value == QuoteTypes::CAR_CAT_A->value;
        if ($isCarRevival) {
            $quoteType = QuoteTypes::CAR;
        }

        if (! auth()->user()->hasAnyRole($quoteType->advisorRoles())) {
            return 'You are not allowed to request buy leads for this quote type';
        }

        if ($this->isRequestAlreadySubmitted($quoteType, $isCarRevival)) {
            return 'You can initiate a new Buy Lead request once the existing requested leads are assigned.';
        }

        $remainingLimit = $this->getBlLeadRemainingLimit($quoteType, $isCarRevival);
        if ($remainingLimit === 'DISABLED' || $remainingLimit <= 0) {
            $message = 'You have reached your maximum buy leads allocation for today';
        } elseif ($request->count > $remainingLimit) {
            $leadStr = Str::plural('Lead', $remainingLimit);
            $isAre = $remainingLimit > 1 ? 'are' : 'is';

            $message = "You have exceeded your remaining buy leads allocation. Your remaining Buy {$leadStr} {$isAre} {$remainingLimit}";
        }

        return $message ?? null;
    }

    public function findConfig(QuoteTypes $quoteType): ?BuyLeadConfiguration
    {
        // Optimized retrieval for BuyLeadConfiguration with Car Revival special handling.
        $departmentId = Auth::user()->department_id ?? 0;
        $isCarRevival = $quoteType->value === QuoteTypes::CAR_CAT_A->value;
        $baseQuoteType = $isCarRevival ? QuoteTypes::CAR : $quoteType;

        $query = BuyLeadConfiguration::where('quote_type_id', $baseQuoteType->id())
            ->where('department_id', $departmentId);

        // For Car Revival, ensure only configs with at least one nationality are considered.
        if ($isCarRevival) {
            $query->where('source', LeadSourceEnum::REVIVAL);
        }

        return $query->first();

    }

    public function findConfigCost(QuoteTypes $quoteType)
    {
        $config = $this->findConfig($quoteType);
        if (! $config) {
            return 'No configuration found for this quote type';

        }

        $cost = null;
        $requestType = null;
        $isCarRevival = $quoteType->value === QuoteTypes::CAR_CAT_A->value;
        $baseQuoteType = $isCarRevival ? QuoteTypes::CAR : $quoteType;

        if (Auth::user()->isValueUser($baseQuoteType)) {
            $cost = $config->value;
            $requestType = 'value';
        } elseif (Auth::user()->isVolumeUser($baseQuoteType)) {
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
        $isCarRevival = $request->quote_type == QuoteTypes::CAR_CAT_A->value;
        $baseQuoteTypeId = $isCarRevival ? QuoteTypes::CAR->id() : $request->getQuoteTypeId();
        BuyLeadRequest::create([
            'quote_type_id' => $baseQuoteTypeId,
            'user_id' => Auth::id(),
            'requested_count' => $request->count,
            'cost_per_lead' => $cost,
            'request_type' => $requestType,
            'expires_at' => null,
            'department_id' => Auth::user()->department_id,
            'segment' => $segment,
            'source' => $isCarRevival ? LeadSourceEnum::REVIVAL : null,
        ]);

        return null;
    }

    public function getActiveRequests()
    {
        return BuyLeadRequest::select('id', 'quote_type_id', 'requested_count', 'allocated_count', 'cost_per_lead', 'source', 'created_at')
            ->selectRaw('CONCAT(ROUND(requested_count * cost_per_lead, 0), " AED") as total_cost')
            ->with('quoteType:id,code')
            ->where('user_id', Auth::id())
            ->latest()
            ->active()
            ->simplePaginate(20)
            ->withQueryString();
    }

    public function getTrackingData(QuoteTypes $quoteType, Carbon $startDate, Carbon $endDate, bool $isExport = false, bool $isCarRevival = false)
    {
        return BuyLeadRequestLog::select(
            'buy_lead_request_logs.id',
            'buy_lead_request_logs.quote_type_id',
            'buy_lead_request_logs.uuid as ref_id',
            'buy_lead_requests.created_at',
            'departments.name as department', 'buy_lead_requests.source')
            ->selectRaw('CONCAT(ROUND(buy_lead_requests.cost_per_lead, 0), " AED") as cost')
            ->with('quoteType:id,code')
            ->join('buy_lead_requests', 'buy_lead_requests.id', '=', 'buy_lead_request_logs.buy_lead_request_id')
            ->join('users', 'users.id', '=', 'buy_lead_requests.user_id')
            ->leftJoin('departments', 'buy_lead_requests.department_id', '=', 'departments.id')
            ->where('buy_lead_requests.user_id', Auth::id())
            ->when(
                ! is_null($isCarRevival),
                fn ($q) => $quoteType === QuoteTypes::CAR
                    ? ($isCarRevival
                        ? $q->where('buy_lead_requests.source', LeadSourceEnum::REVIVAL)
                        : $q->where(function ($query) {
                            $query->whereNull('buy_lead_requests.source')
                                ->orWhere('buy_lead_requests.source', '');
                        })
                    )
                    : $q
            )
            ->where('buy_lead_request_logs.quote_type_id', $quoteType->id())
            ->whereBetween('buy_lead_request_logs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->latest('buy_lead_request_logs.created_at')
            ->when($isExport,
                fn ($q) => $q->get(),
                fn ($q) => $q->simplePaginate(20)->withQueryString(),
            );

    }

    public function exportTrackingReportPDF(QuoteTypes $quoteType, Carbon $startDate, Carbon $endDate, bool $isCarRevival = false)
    {
        $data['list'] = $this->getTrackingData($quoteType, $startDate, $endDate, true, $isCarRevival);

        $data['quoteType'] = $quoteType;
        $pdf = PDF::loadView('pdf.buy-lead-requests', $data);

        $pdfName = 'InsuranceMarket.ae™ Buy Leads Tracking Report.pdf';

        return $pdf->download($pdfName);
    }

    public static function getNationalitiesIds(QuoteTypes $quoteType)
    {
        return BuyLeadConfigurationNationality::where('quote_type', $quoteType)->pluck('nationality_id')->toArray();
    }

    public function getAllRequestsForAdmin(array $filters = [])
    {
        $query = BuyLeadRequest::query()
            ->with(['user:id,name,email,employee_code', 'quoteType:id,code', 'department:id,name'])
            ->select('buy_lead_requests.*');

        // Apply filters
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['quote_type'])) {
            $quoteType = QuoteTypes::from($filters['quote_type']);

            $isCarRevival = $quoteType->value === QuoteTypes::CAR_CAT_A->value;
            $baseQuoteType = $isCarRevival ? QuoteTypes::CAR : $quoteType;

            $query->where('quote_type_id', $baseQuoteType->id());

            if ($isCarRevival) {
                // Filter for Car Revival: source must be REVIVAL
                $query->where('source', LeadSourceEnum::REVIVAL);
            } elseif ($quoteType === QuoteTypes::CAR) {
                // Filter for regular Car: source must NOT be REVIVAL
                $query->where(function ($q) {
                    $q->where('source', '!=', LeadSourceEnum::REVIVAL)
                        ->orWhereNull('source')
                        ->orWhere('source', '');
                });
            }
        }

        if (! empty($filters['status'])) {
            $status = $filters['status'];

            if ($status === 'completed') {
                $query->computedCompleted();
            } elseif ($status === 'expired') {
                $query->computedExpired();
            } else {
                // For 'active' and 'processing' statuses
                $query->computedActiveStatus($status);
            }
        }

        if (! empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (! empty($filters['date']) && is_array($filters['date']) && count($filters['date']) === 2) {
            $startDate = $filters['date'][0] !== null ? Carbon::parse($filters['date'][0])->startOfDay() : null;
            $endDate = $filters['date'][1] !== null ? Carbon::parse($filters['date'][1])->endOfDay() : null;

            if ($startDate !== null && $endDate !== null) {
                $query->whereBetween('buy_lead_requests.created_at', [$startDate, $endDate]);
            } elseif ($startDate !== null) {
                $query->where('buy_lead_requests.created_at', '>=', $startDate);
            } elseif ($endDate !== null) {
                $query->where('buy_lead_requests.created_at', '<=', $endDate);
            }
        }

        return $query->latest('buy_lead_requests.created_at')
            ->simplePaginate(20)
            ->withQueryString();
    }

}
