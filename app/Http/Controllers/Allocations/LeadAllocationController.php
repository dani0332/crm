<?php

namespace App\Http\Controllers\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Services\LeadAllocationDashboardService;
use App\Services\LeadAllocationPermissionService;
use App\Traits\TeamHierarchyTrait;

class LeadAllocationController extends Controller
{
    use TeamHierarchyTrait;

    private QuoteTypes $quoteType;
    private LeadAllocationDashboardService $leadAllocationDashboardService;

    public function __construct(LeadAllocationDashboardService $leadAllocationDashboardService)
    {
        $this->quoteType = QuoteTypes::from(request('quoteType'));
        $this->leadAllocationDashboardService = $leadAllocationDashboardService;

        $permissions = match ($this->quoteType) {
            QuoteTypes::CORPLINE => [
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::CYCLE => [
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::PET => [
                PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::PET_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::YACHT => [
                PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::YACHT_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::LIFE => [
                PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::LIFE_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::HOME => [
                PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::HOME_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::SAVINGS => [
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::GROUP_MEDICAL => [
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::TRAVEL => [
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
                PermissionsEnum::TRAVEL_SIC_ALLOCATION,
            ],
            QuoteTypes::CYBER => [
                PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CYBER_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::DEVICE => [
                PermissionsEnum::DEVICE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::DEVICE_LEAD_ALLOCATION_VIEW_ONLY,
                PermissionsEnum::DEVICE_LEAD_ALLOCATION_EDIT,
            ],
        };

        $this->middleware('permission:'.implode('|', $permissions), ['only' => ['index']]);
    }

    public function index(QuoteTypes $quoteType)
    {
        $totalAssignedLeadCount = 0;
        $availableUsers = 0;
        $unAvailableUsers = 0;

        $todayTotalLeadCount = $this->leadAllocationDashboardService->getTodaysTotalLeadsCount($quoteType);
        $todayTotalUnAssignedLeadCount = $this->leadAllocationDashboardService->getTodaysTotalUnAssignedLeadsCount($quoteType);
        $data = $this->leadAllocationDashboardService->getAdvisors($quoteType);

        foreach ($data as $value) {
            $totalAssignedLeadCount = $totalAssignedLeadCount + $value->allocationCount;
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        $data = [
            'totalAssignedLeadCount' => $totalAssignedLeadCount,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'todayTotalLeadCount' => $todayTotalLeadCount,
            'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
            'quoteType' => $quoteType->value,
            'quoteTypes' => QuoteTypes::withLabels(),
            'data' => $data,
            'lobSpecificLeadAllocation' => $this->lobSpecificLeadAllocation(),
            'canMutateLeadAllocation' => LeadAllocationPermissionService::userCanMutate($quoteType),
            'isSavings' => $quoteType == QuoteTypes::SAVINGS,
        ];

        if ($quoteType == QuoteTypes::SAVINGS) {
            $data['todayTotalRegularUnAssignedLeadCount'] = $this->leadAllocationDashboardService->getTodaysFrequencyBasedUnAssignedLeadsCount($quoteType, InvestmentFrequencyEnum::REGULAR);
            $data['todayTotalLumpsumUnAssignedLeadCount'] = $this->leadAllocationDashboardService->getTodaysFrequencyBasedUnAssignedLeadsCount($quoteType, InvestmentFrequencyEnum::LUMPSUM);
        }

        return inertia('LeadAllocation/Index', $data);
    }

    private function lobSpecificLeadAllocation(): bool
    {
        $permissions = match ($this->quoteType) {
            QuoteTypes::CORPLINE => [
                PermissionsEnum::CORPLINE_LEADPOOL,
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::CYCLE => [
                PermissionsEnum::CYCLE_LEADPOOL,
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::PET => [
                PermissionsEnum::PET_LEADPOOL,
                PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::YACHT => [
                PermissionsEnum::YACHT_LEADPOOL,
                PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::LIFE => [
                PermissionsEnum::LIFE_LEADPOOL,
                PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::HOME => [
                PermissionsEnum::HOME_LEADPOOL,
                PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::SAVINGS => [
                PermissionsEnum::SAVINGS_LEADPOOL,
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::GROUP_MEDICAL => [
                PermissionsEnum::GROUP_MEDICAL_LEADPOOL,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::TRAVEL => [
                PermissionsEnum::TRAVEL_LEADPOOL,
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::CYBER => [
                PermissionsEnum::CYBER_LEADPOOL,
                PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::DEVICE => [
                PermissionsEnum::DEVICE_LEADPOOL,
                PermissionsEnum::DEVICE_LEAD_ALLOCATION_EDIT,
            ],
        };

        return request()->user()->hasAnyPermission($permissions);
    }
}
