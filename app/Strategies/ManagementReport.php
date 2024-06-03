<?php

namespace App\Strategies;

use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\LeadSource;
use App\Models\Lookup;
use App\Models\Team;
use App\Services\ApplicationStorageService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;

class ManagementReport
{
    use TeamHierarchyTrait;

    public function getFilterOptions()
    {

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $loginUserId = auth()->user()->id;

        $advisors = [];

        $teamIds = $this->getUserTeams($loginUserId);

        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $reportCategories = [];
        foreach (ManagementReportCategoriesEnum::asArray() as $value) {
            $reportCategories[] = ['label' => $value, 'value' => $value];
        }

        $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->get()
            ->map(fn ($item) => ['label' => $item->text, 'value' => $item->text])
            ->prepend(['label' => 'All', 'value' => ''], 'value')
            ->sortBy('label')
            ->values()
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
            'reportCategories' => $reportCategories,
            'transactionTypes' => $transactionTypes,
        ];
    }
    public function applyFilters($query, $request)
    {
        //secondOptionalFieldName to be used in case of transaction due date, coming from a different table 'payment_splits'
        $dateFilter = function ($fieldName, $filterKey, $secondOptionalFieldName = null) use ($query, $request) {

            if ($request[$filterKey] != null) {
                if (is_array($request[$filterKey])) {
                    $dates = [];
                    foreach ($request[$filterKey] as $key => $dateString) {
                        $carbonDate = Carbon::parse($dateString);
                        if ($key == 0) {
                            $dates[$key] = $carbonDate->startOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        } else {
                            $dates[$key] = $carbonDate->endOfDay()->format(config('constants.DB_DATE_FORMAT_MATCH'));
                        }
                    }
                    $request[$filterKey] = $dates;
                } else {
                    $carbonDate = Carbon::parse($request[$filterKey]);
                    $dates = $carbonDate->startOfDay();
                    $request[$filterKey] = $dates;
                }
            }
            $dateRange = $request[$filterKey] ?? [
                Carbon::parse(now())->startOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
                Carbon::parse(now())->endOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
            ];

            if ($secondOptionalFieldName) {
                $query->where(function ($query) use ($fieldName, $dateRange, $secondOptionalFieldName) {
                    $query->whereBetween($fieldName, $dateRange)
                        ->orWhereBetween($secondOptionalFieldName, $dateRange);
                });
            } else {
                $query->whereBetween($fieldName, $dateRange);
            }

        };

        switch ($request['reportCategory']) {
            case ManagementReportCategoriesEnum::SALE_SUMMARY:
            case ManagementReportCategoriesEnum::SALE_DETAIL:
                if ($request['reportType'] == ManagementReportTypeEnum::BOOKED_POLICIES) {
                    $dateFilter('personal_quotes.policy_booking_date', 'policyBookDate');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                }
                break;

            case ManagementReportCategoriesEnum::ENDING_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::EXPIRING_POLICIES) {
                    $dateFilter('p.policy_expiry_date', 'policyExpiredDate');
                }
                break;

            case ManagementReportCategoriesEnum::TRANSACTION:
                if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('p.payment_due_date', 'paymentDueDate', 'ps.due_date');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::BOOKED_POLICIES) {
                    $dateFilter('personal_quotes.policy_booking_date', 'policyBookDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::ACTIVE_POLICIES) {
                    $dateFilter = $request['createdAt'] ?? now()->startOfDay()->format(config('constants.DATE_FORMAT_ONLY'));
                    $query->where(function ($query) use ($dateFilter) {
                        $query->where('policy_start_date', '>=', $dateFilter)
                            ->orWhere('p.policy_expiry_date', '<=', $dateFilter);
                    });
                }
                break;
        }

        if (isset($request['transactionType'])) {
            $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES);
            $typeCode = $request['transactionType'];
            if ($typeCode !== null) {
                $type = $transactionTypes->where('text', $typeCode)->first();
                if ($type !== null) {
                    $typeId = $type->id;
                    $query->where('transaction_type_id', $typeId);
                }
            }
        }

        if (isset($request['teams']) && count($request['teams']) > 0) {
            $value = $request['teams'];
            $query->whereIn('t.id', $value);
        } else {
            $teamIds = $this->getUserTeams(auth()->user()->id);
            $query->whereIn('t.id', $teamIds->pluck('id'));
        }

        if (isset($request['subTeams']) && ! empty($request['subTeams'])) {
            $query->whereIn('u.sub_team_id', $request['subTeams']);
        }

        if (isset($request['leadSources']) && ! empty($request['leadSources'])) {
            $query->whereIn('personal_quotes.source', $request['leadSources']);
        }

        if (isset($request['includeCancelledPolicies']) && ! empty($request['includeCancelledPolicies'])) {
            if ($request['includeCancelledPolicies'] == 'Yes') {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyCancelled);
            } else {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyBooked);
            }
        }
    }

    public function getUtmGroup($request, $query)
    {
        if (isset($request['utmGroupBy']) && ! empty($request['utmGroupBy'])) {
            switch ($request['utmGroupBy']) {
                case 'UTM Campaign':
                    $query->addSelect('pqd.utm_campaign');
                    $query->whereNotNull('pqd.utm_campaign');

                    return 'pqd.utm_campaign';
                    break;
                case 'UTM Source':
                    $query->addSelect('pqd.utm_source');
                    $query->whereNotNull('pqd.utm_source');

                    return 'pqd.utm_source';
                    break;
                case 'UTM Medium':
                    $query->addSelect('pqd.utm_medium');
                    $query->whereNotNull('pqd.utm_medium');

                    return 'pqd.utm_medium';
                    break;
            }
        }
    }
}
