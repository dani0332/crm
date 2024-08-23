<?php

namespace App\Factories;

use App\Enums\GenericRequestEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportGroupByEnum;
use App\Models\LeadSource;
use App\Models\Team;
use App\Services\ApplicationStorageService;
use App\Services\Reports\ActivePoliciesReportService;
use App\Services\Reports\EndingPoliciesReportService;
use App\Services\Reports\EndorsementReportService;
use App\Services\Reports\InstallmentReportService;
use App\Services\Reports\SaleDetailReportService;
use App\Services\Reports\SaleSummaryReportService;
use App\Services\Reports\TransactionReportService;
use App\Traits\TeamHierarchyTrait;

class ManagementReportServiceFactory
{
    use TeamHierarchyTrait;

    public static function createStrategy($reportCategory)
    {
        $strategy = null;
        if ($reportCategory == ManagementReportCategoriesEnum::SALE_SUMMARY) {
            $strategy = new SaleSummaryReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::SALE_DETAIL) {
            $strategy = new SaleDetailReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::ENDING_POLICIES) {
            $strategy = new EndingPoliciesReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::TRANSACTION) {
            $strategy = new TransactionReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::ACTIVE_POLICIES) {
            $strategy = new ActivePoliciesReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::INSTALLMENT) {
            $strategy = new InstallmentReportService;
        } elseif ($reportCategory == ManagementReportCategoriesEnum::ENDORSEMENT) {
            $strategy = new EndorsementReportService;
        } else {
            info('No strategy found for report category : '.$reportCategory);
        }

        return $strategy;
    }

    public static function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $managementReportCategories = [];
        foreach (ManagementReportCategoriesEnum::asArray() as $value) {
            $managementReportCategories[] = ['label' => $value, 'value' => $value];
        }

        $managementReportGroupBy = [];
        foreach (ManagementReportGroupByEnum::asArray() as $value) {
            $managementReportGroupBy[] = ['label' => $value, 'value' => $value];
        }

        $loginUserId = auth()->user()->id;

        $teamIds = TeamHierarchyTrait::getUserTeams($loginUserId);

        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        return [
            'maxDays' => $maxDays,
            'leadSources' => $leadSources,
            'teams' => $teams,
            'managementReportCategories' => $managementReportCategories,
            'managementReportGroupBy' => $managementReportGroupBy,
        ];
    }
}
