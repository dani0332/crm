<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait Reportable
{
    public function getStartAndEndDate($filters, $dateAttribute = 'advisorAssignedDates')
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        if (isset($filters->{$dateAttribute})) {
            $startDate = Carbon::parse($filters->{$dateAttribute}[0])->startOfDay()->format($dateFormat);
        } else {
            $startDate = $freshLoad
                ? now()->startOfDay()->format($dateFormat)
                : now()->subDays((int) $maxDays)->startOfDay()->format($dateFormat);
        }

        $endDate = isset($filters->{$dateAttribute})
            ? Carbon::parse($filters->{$dateAttribute}[1])->endOfDay()->format($dateFormat)
            : now()->endOfDay()->format($dateFormat);

        return [$freshLoad, $startDate, $endDate];
    }

    public function getExcludedSources()
    {
        return [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY];
    }

    public function getNotInterestedStatuses()
    {
        return [
            QuoteStatusEnum::PriceTooHigh,
            QuoteStatusEnum::PolicyPurchasedBeforeFirstCall,
            QuoteStatusEnum::NotInterested,
            QuoteStatusEnum::NotEligibleForInsurance,
            QuoteStatusEnum::NotLookingForMotorInsurance,
            QuoteStatusEnum::NonGccSpec,
            QuoteStatusEnum::AMLScreeningFailed,
        ];
    }

    public function getInProgressStatuses()
    {
        return [
            QuoteStatusEnum::NotContactablePe,
            QuoteStatusEnum::FollowupCall,
            QuoteStatusEnum::Interested,
            QuoteStatusEnum::NoAnswer,
            QuoteStatusEnum::Quoted,
            QuoteStatusEnum::PaymentPending,
            QuoteStatusEnum::AMLScreeningCleared,
            QuoteStatusEnum::PendingQuote,
        ];
    }

    public function getBadLeadStatuses()
    {
        return [
            QuoteStatusEnum::Duplicate,
            QuoteStatusEnum::Fake,
        ];
    }

    public function getPaidStatuses()
    {
        return [
            PaymentStatusEnum::CAPTURED,
        ];
    }

    public function getUserPorductName()
    {
        $productName = '';
        // Get the products associated with the authenticated user
        $products = $this->getUserProducts(auth()->user()->id)->where('name', '!=', quoteTypeCode::Car);
        if (count($products) === 1) {
            $productName = $products->first()->name;
        }

        return $productName;
    }

    protected function getQuoteType($request)
    {
        // Return the 'lob' parameter from the request if it exists, otherwise return the user's product name
        return $request['lob'] ?? $this->getUserPorductName();
    }

    private function isAdvisorManager()
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }
        // Check if the user is a manager or deputy and lacks the permission to view the manager retention report
        if (
            (auth()->user()->isManagerOrDeputy() && ! Auth::user()->can(PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW)) ||
            // Check if the user is an advisor and lacks the permission to view the advisor retention report
            (auth()->user()->isAdvisor() && ! Auth::user()->can(PermissionsEnum::ADVISOR_RETENTION_REPORT_VIEW))
        ) {
            return false;
        }

        return true;
    }

    private function applyAdvisorFilters($query, $request)
    {
        // Check if advisors parameter is set and contains values
        if (isset($request['advisors']) && count($request['advisors']) > 0) {
            // Apply the advisor filter to the query
            $query->whereIn('advisor_id', $request['advisors']);
        }
    }

    public function getLobByPermissions()
    {
        return [
            quoteTypeCode::Bike => quoteTypeCode::Bike,
            quoteTypeCode::Health => quoteTypeCode::Health,
            quoteTypeCode::Travel => quoteTypeCode::Travel,
            quoteTypeCode::Pet => quoteTypeCode::Pet,
            quoteTypeCode::Cycle => quoteTypeCode::Cycle,
            quoteTypeCode::Yacht => quoteTypeCode::Yacht,
            quoteTypeCode::Life => quoteTypeCode::Life,
            quoteTypeCode::Home => quoteTypeCode::Home,
            quoteTypeCode::CORPLINE => quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical => quoteTypeCode::GroupMedical,

        ];
    }

    public function getFiltersByLob()
    {
        // Determine the visibility of each LOB based on the user's roles
        $canView = [
            quoteTypeCode::Bike => ! Auth::user()->hasRole(RolesEnum::BikeAdvisor),
            quoteTypeCode::Health => ! Auth::user()->hasRole(RolesEnum::RMAdvisor),
            quoteTypeCode::Travel => ! Auth::user()->hasRole(RolesEnum::TravelAdvisor),
            quoteTypeCode::Pet => ! Auth::user()->hasRole(RolesEnum::PetAdvisor),
            quoteTypeCode::Cycle => ! Auth::user()->hasRole(RolesEnum::CycleAdvisor),
            quoteTypeCode::Yacht => ! Auth::user()->hasRole(RolesEnum::YachtAdvisor),
            quoteTypeCode::Life => ! Auth::user()->hasRole(RolesEnum::LifeAdvisor),
            quoteTypeCode::Home => ! Auth::user()->hasRole(RolesEnum::HomeAdvisor),
            quoteTypeCode::CORPLINE => ! Auth::user()->hasRole(RolesEnum::CorpLineAdvisor),
            quoteTypeCode::GroupMedical => ! Auth::user()->hasRole(RolesEnum::GMAdvisor),
        ];

        // Return the filter options with their visibility settings
        return [
            'advisors' => [
                'can_view' => $canView,
            ],
            'teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'previous_policy_expiry_date' => [
                'can_view' => $canView,
            ],
            'department' => [
                'lobs' => [
                    quoteTypeCode::Health,
                ],
            ],
        ];
    }

    protected function calculateAdvisorRetentionPercentage($avgVolumeNetRetention, $volumeNetRetention)
    {
        return number_format((((float) $volumeNetRetention) - ((float) $avgVolumeNetRetention)), 2).'%';
    }

    protected function calculateRetentionPercentage($sales, $total)
    {
        return ($total != 0) ? number_format(($sales / $total) * 100, 2).'%' : '0.00%';
    }

    protected function aggregateReportData($reportData)
    {
        $isReportDataExist = count($reportData) !== 0;

        return [
            'total' => $isReportDataExist ? $reportData->sum('total') : 0,
            'sales' => $isReportDataExist ? $reportData->sum('sales') : 0,
            'invalid' => $isReportDataExist ? $reportData->sum('invalid') : 0,
            'lost' => $isReportDataExist ? $reportData->sum('lost') : 0,
        ];
    }
}
