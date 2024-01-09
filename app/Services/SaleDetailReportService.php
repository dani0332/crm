<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportGroupByEnum;
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

class SaleDetailReportService implements ManagementReport
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
            ->select(
                'policy_number',
                'policy_start_date',
                'policy_due_date',
                'source',
                DB::raw('FORMAT(personal_quotes.price_vat_applicable, 2) as price_vat_applicable'),
                DB::raw('FORMAT(payments.discount_value,2) as discount'),
                DB::raw('FORMAT(((price_vat_applicable + price_vat_not_applicable + vat) - payments.discount_value),2) as total_price'),
                DB::raw('FORMAT(payments.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(payments.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(payments.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('(commission_vat_applicable + commission_vat) as total_commission'),
                DB::raw("'collects' as collects"),
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'payments.captured_at as date_paid',
                DB::raw('FORMAT(personal_quotes.premium_captured,2) as collected_amount'),
                DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"),
                DB::raw("'customer_type' as customer_type"),
                'quote_type.code as line_of_business',
                DB::raw("'sub_type_line_of_business' as sub_type_line_of_business"),
                'u.name as advisor_name',
                'pi.name as policy_issuer',
            )
            ->leftJoin('payments', 'personal_quotes.code', '=', 'payments.code')
            ->leftJoin('payment_status', 'payment_status.id', '=', 'payments.payment_status_id')
            ->leftJoin('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'policy_issuer_id')
            ->when($request->groupBy, function ($query, $groupBy) {
                return $query->groupBy($this->resolveGroupByColumn($groupBy));
            });

        $this->applyFilters($query, $filters);

        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'payments.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'payments.insurer_id',
            'advisor' => 'u.name',
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
        $advisorAssignedDates = [ManagementReportCategoriesEnum::SALE_DETAIL];

        return [
            'managementReportCategories' => $advisorAssignedDates,
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
                if ($filters['reportType'] == ManagementReportTypeEnum::ISSUED_POLICIES) {
                    $dateFilter('personal_quotes.policy_issuance_date', 'policyIssuanceDate');
                } elseif ($filters['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
                    $dateFilter('payments.payment_due_date', 'paymentDueDate');
                }
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
