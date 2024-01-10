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
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_SUMMARY;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;
        $request['policyIssuanceDate'] = $request->policyIssuanceDate ?? [
            Carbon::parse(now())->startOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
            Carbon::parse(now())->endOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
        ];
        $request['groupBy'] = $request->groupBy ?? 'advisor';

        $query = PersonalQuote::query()
            ->leftJoin('send_updates', 'personal_quotes.uuid', '=', 'send_updates.quote_uuid')
            ->leftJoin('lookups', 'send_updates.type_id', '=', 'lookups.id')
            ->leftJoin('users', 'personal_quotes.advisor_id', '=', 'users.id')
            ->leftJoin('user_team', 'users.id', '=', 'user_team.user_id')
            ->leftJoin('teams', 'user_team.team_id', '=', 'teams.id')
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

        if ($request->groupBy == 'advisor') {
            $query->addSelect('users.name as advisor');
            $query->whereNotNull('advisor_id');
        }

        if ($request->groupBy == 'customer_group') {
            $query->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_group"));
            $query->whereNotNull('customer_id');
        }

        if ($request->groupBy == 'insurer') {
            $query->join('insurance_provider', 'insurance_provider.id', '=', 'payments.insurance_provider_id')
                ->addSelect('insurance_provider.text as insurer');
            $query->whereNotNull('payments.insurance_provider_id');
        }

        if ($request->groupBy == 'policy_issuer') {
            $query->leftJoin('users as pi', 'pi.id', '=', 'payments.policy_issuer_id')
                ->addSelect('pi.name as policy_issuer');
            $query->whereNotNull('payments.policy_issuer_id');
        }

        if ($request->groupBy == 'line_of_business') {
            $query->addSelect('quote_type.code as line_of_business');
            $query->whereNotNull('quote_type.code');
        }

        $this->applyFilters($query, $request);
        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'payments.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'payments.insurance_provider_id',
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

    public function applyFilters($query, $request)
    {
        $dateFilter = function ($fieldName, $filterKey) use ($query, $request) {
            $dateRange = $request[$filterKey] ?? [
                Carbon::parse(now())->startOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
                Carbon::parse(now())->endOfDay()->format(config('constants.DATE_FORMAT_ONLY')),
            ];
            if (isset($request[$filterKey])) {
                $query->whereBetween($fieldName, $dateRange);
            }
        };

        switch ($request['reportCategory']) {
            case ManagementReportCategoriesEnum::SALE_SUMMARY:
            case ManagementReportCategoriesEnum::SALE_DETAIL:
                if ($request['reportType'] == ManagementReportTypeEnum::ISSUED_POLICIES) {
                    $dateFilter('personal_quotes.policy_issuance_date', 'policyIssuanceDate');
                } elseif ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('payments.payment_due_date', 'paymentDueDate');
                }
                break;

            case ManagementReportCategoriesEnum::ENDING_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::EXPIRING_POLICIES) {
                    $dateFilter('payments.policy_expiry_date', 'policyExpiredDate');
                }
                break;

            case ManagementReportCategoriesEnum::TRANSACTION:
                if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('payments.payment_due_date', 'paymentDueDate');
                }
                break;

            case ManagementReportCategoriesEnum::ACTIVE_POLICIES:
                if ($request['reportType'] == ManagementReportTypeEnum::ACTIVE_POLICIES) {
                    $dateFilter('personal_quotes.created_at', 'createdAt');
                }
                break;
        }

        if (isset($request['transactionType'])) {
            $transactionTypes = Lookup::where('key', LookupsEnum::TRANSACTION_TYPES)->get();

            switch ($request['transactionType']) {
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

        if (isset($request['teams']) && ! empty($request['teams'])) {
            $query->whereIn('teams.id', $request['teams']);
        }

        if (isset($request['subTeams']) && ! empty($request['subTeams'])) {
            $query->whereIn('users.sub_team_id', $request['subTeams']);
        }

        if (isset($request['leadSource']) && ! empty($request['leadSource'])) {
            $query->whereIn('personal_quotes.source', $request['leadSource']);
        }

        if (isset($request['includeCancelPolicies']) && ! empty($request['includeCancelPolicies'])) {
            if ($request['includeCancelPolicies'] == 'Yes') {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyCancelled);
            } else {
                $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::PolicyBooked);
            }
        }
    }

}
