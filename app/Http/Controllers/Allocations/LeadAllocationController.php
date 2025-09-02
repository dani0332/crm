<?php

namespace App\Http\Controllers\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\LeadAllocationUserBLStatusFiltersEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Services\LeadAllocationDashboardService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;

class LeadAllocationController extends Controller
{
    use TeamHierarchyTrait;

    private QuoteTypes $quoteType;
    private LeadAllocationDashboardService $leadAllocationDashboardService;

    public function __construct(LeadAllocationDashboardService $leadAllocationDashboardService)
    {
        $this->quoteType = QuoteTypes::from(request('quoteType'));
        $this->leadAllocationDashboardService = $leadAllocationDashboardService;

        $permission = match ($this->quoteType) {
            QuoteTypes::CORPLINE => PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::CYCLE => PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::PET => PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::YACHT => PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::LIFE => PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::HOME => PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::SAVINGS => PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::GROUP_MEDICAL => PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::TRAVEL => PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD,
        };

        $this->middleware("permission:{$permission}", ['only' => ['index']]);
    }

    public function index(QuoteTypes $quoteType, Request $request)
    {
        $totalAssignedLeadCount = 0;
        $availableUsers = 0;
        $unAvailableUsers = 0;

        $todayTotalLeadCount = $this->leadAllocationDashboardService->getTodaysTotalLeadsCount($quoteType);
        $todayTotalUnAssignedLeadCount = $this->leadAllocationDashboardService->getTodaysTotalUnAssignedLeadsCount($quoteType);
        $data = $this->leadAllocationDashboardService->getAdvisors($quoteType, $request->userBlStatus);

        foreach ($data as $value) {
            $totalAssignedLeadCount = $totalAssignedLeadCount + $value->allocationCount;
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        $data = [
            'userBLStatuses' => LeadAllocationUserBLStatusFiltersEnum::withLabels(),
            'totalAssignedLeadCount' => $totalAssignedLeadCount,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'todayTotalLeadCount' => $todayTotalLeadCount,
            'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
            'quoteType' => $quoteType->value,
            'data' => $data,
            'lobSpecificLeadAllocation' => $this->lobSpecificLeadAllocation(),
            'isSavings' => $quoteType == QuoteTypes::SAVINGS,
        ];

        if ($quoteType == QuoteTypes::SAVINGS) {
            $data['todayTotalRegularUnAssignedLeadCount'] = $this->leadAllocationDashboardService->getTodaysFrequencyBasedUnAssignedLeadsCount($quoteType, InvestmentFrequencyEnum::REGULAR);
            $data['todayTotalLumpsumUnAssignedLeadCount'] = $this->leadAllocationDashboardService->getTodaysFrequencyBasedUnAssignedLeadsCount($quoteType, InvestmentFrequencyEnum::LUMPSUM);
        }

        return inertia('LeadAllocation/Index', $data);
    }

    private function lobSpecificLeadAllocation()
    {
        $permission = match ($this->quoteType) {
            QuoteTypes::CORPLINE => PermissionsEnum::CORPLINE_LEADPOOL,
            QuoteTypes::CYCLE => PermissionsEnum::CYCLE_LEADPOOL,
            QuoteTypes::PET => PermissionsEnum::PET_LEADPOOL,
            QuoteTypes::YACHT => PermissionsEnum::YACHT_LEADPOOL,
            QuoteTypes::LIFE => PermissionsEnum::LIFE_LEADPOOL,
            QuoteTypes::HOME => PermissionsEnum::HOME_LEADPOOL,
            QuoteTypes::SAVINGS => PermissionsEnum::SAVINGS_LEADPOOL,
            QuoteTypes::GROUP_MEDICAL => PermissionsEnum::GROUP_MEDICAL_LEADPOOL,
            QuoteTypes::TRAVEL => PermissionsEnum::TRAVEL_LEADPOOL,
        };

        return request()->user()->can($permission);
    }
}
