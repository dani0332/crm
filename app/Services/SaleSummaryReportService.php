<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\LeadSource;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleSummaryReportService implements ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        $filters = [
            'reportCategory' => $request->reportCategory,
            'reportType' => $request->reportType,
            'policyIssuanceDate' => $request->policyIssuanceDate,
            'paymentDueDate' => $request->paymentDueDate,
            'policyExpiredDate' => $request->policyExpiredDate,
            'createdAt' => $request->createdAt,
            'transactionType' => $request->transactionType,
            'teams' => $request->teams,
            'subTeams' => $request->subTeams,
            'leadSource' => $request->leadSource,
            'includeCancelPolicies' => $request->includeCancelPolicies,
            'groupBy' => $request->groupBy,
            'utmGroupBy' => $request->utmGroupBy,
            'page' => $request->page,
        ];

        $query = PersonalQuote::query()
            ->leftJoin('send_updates', 'personal_quotes.uuid', '=', 'send_updates.quote_uuid')
            ->leftJoin('lookups', 'send_updates.type_id', '=', 'lookups.id')
            ->leftJoin('users', 'personal_quotes.advisor_id', '=', 'users.id')
            ->leftJoin('user_team', 'users.id', '=', 'user_team.user_id')
            ->leftJoin('teams', 'user_team.team_id', '=', 'teams.id')
            ->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->join('payments', 'personal_quotes.code', '=', 'payments.code')
            ->select(
                DB::raw('SUM(CASE WHEN COALESCE(policy_issuance_date, policy_number) IS NOT NULL THEN 1 ELSE 0 END) as total_policies'),
                DB::raw('SUM(CASE WHEN send_updates.id IS NOT NULL AND lookups.code = "Financial" THEN 1 ELSE 0 END) as total_endorsements'),
                DB::raw('SUM(CASE WHEN COALESCE(policy_issuance_date, policy_number) IS NOT NULL THEN 1 ELSE 0 END) + SUM(CASE WHEN send_updates.id IS NOT NULL AND lookups.code = "Financial" THEN 1 ELSE 0 END) as total_transaction'),
                DB::raw('SUM(price_vat_applicable) as price_vat_applicable'),
                DB::raw('(SUM(price_vat_applicable)* 0.05)  as total_vat'),
                DB::raw('SUM(price_vat_not_applicable) as price_vat_not_applicable'),
                DB::raw('SUM(payments.discount_value) as discount'),
                DB::raw('(SUM(payments.commission_vat_applicable) ) as commission_vat_applicable'),
                DB::raw('(SUM(price_vat_applicable) + SUM(price_vat_not_applicable) + (SUM(price_vat_applicable)* 0.05))  - SUM(payments.discount_value) as total_price'),
            )
            ->when($request->groupBy, function ($query, $groupBy) {
                return $query->groupBy($this->resolveGroupByColumn($groupBy));
            });
        // add users.name as advisor only if group by is advisor
        if($request->groupBy == 'advisor'){
            $query->addSelect('users.name as advisor');
            $query->whereNotNull('advisor_id');
        }

        // add customers.name as customer only if group by is customer
        if ($request->groupBy == 'customer_group') {
            $query->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_group"));
            $query->whereNotNull('customer_id');
        }

        // add insurer.name as insurer only if group by is insurer
        if ($request->groupBy == 'insurer') {
            $query->addSelect('insurer.name as insurer');
            $query->whereNotNull('insurer_id');
        }


        $this->applyFilters($query, $filters);
        //dd($query->toSql(),  $query->getBindings());
        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'payments.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'payments.insurer_id',
            'advisor' => 'users.name',
            'line_of_business' => 'quote_type.code',
        ];

        return $mapping[$groupBy] ?? $groupBy;
    }

    public function getFilterOptions()
    {

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $loginUserId = auth()->user()->id;

        $teamIds = $this->getUserTeams($loginUserId);

        $reportCategories = [];
        foreach (ManagementReportCategoriesEnum::asArray() as $value) {
            $reportCategories[] = ['label' => $value, 'value' => $value];
        }

        $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->get()
            ->map(fn ($item) => ['label' => $item->text, 'value' => $item->id])
            ->prepend(['label' => 'All', 'value' => ''], 'value')
            ->sortBy('label')
            ->values()
            ->toArray();

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
            'reportCategories' => $reportCategories,
            'transactionTypes' => $transactionTypes,
        ];
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $defaultDate = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'policyIssuanceDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::SALE_SUMMARY,
            'reportType' => ManagementReportTypeEnum::ISSUED_POLICIES,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $dateFilter = function ($fieldName, $filterKey) use ($query, $filters) {
            $dateRange = $filters[$filterKey] ?? [
                Carbon::parse(now())->startOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
                Carbon::parse(now())->endOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
            ];
            if (isset($filters[$filterKey])) {
                $query->whereBetween($fieldName, $dateRange);
            }
        };

        switch ($filters['reportCategory']) {
            case ManagementReportCategoriesEnum::SALE_SUMMARY:
            case ManagementReportCategoriesEnum::SALE_DETAIL:
                // if ($filters['reportType'] == ManagementReportTypeEnum::ISSUED_POLICIES) {
                //     $dateFilter('personal_quotes.policy_issuance_date', 'policyIssuanceDate');
                // } elseif ($filters['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                //     $dateFilter('payments.payment_due_date', 'paymentDueDate');
                // }
                break;

            case ManagementReportCategoriesEnum::ENDING_POLICIES:
                if ($filters['reportType'] == ManagementReportTypeEnum::EXPIRING_POLICIES) {
                    $dateFilter('payments.policy_expiry_date', 'policyExpiredDate');
                }
                break;

            case ManagementReportCategoriesEnum::TRANSACTION:
                if ($filters['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('payments.payment_due_date', 'paymentDueDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($filters['reportType'] == ManagementReportTypeEnum::ACTIVE_POLICIES) {
                    $dateFilter('personal_quotes.created_at', 'createdAt');
                }
                break;
        }

        if (isset($filters['transactionType'])) {
            $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES)->get();

            switch ($filters['transactionType']) {
                case TransactionTypeEnum::ENDORSEMENT:
                    $typeCode = TransactionTypeEnum::ENDORSEMENT;
                    break;
                case TransactionTypeEnum::NEW_BUSINESS:
                    $typeCode = TransactionTypeEnum::NEW_BUSINESS;
                    break;
                case TransactionTypeEnum::EXISTING_CUSTOMER_RENEWAL:
                    $typeCode = TransactionTypeEnum::EXISTING_CUSTOMER_RENEWAL;
                    break;
                case TransactionTypeEnum::EXISTING_CUSTOMER_NEW_BUSINESS:
                    $typeCode = TransactionTypeEnum::EXISTING_CUSTOMER_NEW_BUSINESS;
                    break;
                default:
                    $typeCode = null;
                    break;
            }

            if ($typeCode !== null) {
                $typeId = $transactionTypes->where('code', $typeCode)->first()->id;
                $query->where('payments.type_id', $typeId);
            }
        }

        if (isset($filters['teams']) && ! empty($filters['teams'])) {
            $query->whereIn('teams.id', $filters['teams']);
        }

        if (isset($filters['subTeams']) && ! empty($filters['subTeams'])) {
            $query->whereIn('users.sub_team_id', $filters['subTeams']);
        }

        if (isset($filters['leadSource']) && ! empty($filters['leadSource'])) {
            $query->whereIn('personal_quotes.source', $filters['leadSource']);
        }

        if (isset($filters['includeCancelPolicies']) && ! empty($filters['includeCancelPolicies'])) {
            if ($filters['includeCancelPolicies'] == 'Yes') {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyCancelled);
            } else {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyBooked);
            }
        }
    }

}
