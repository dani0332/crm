<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\LeadSource;
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
            ->groupBy('users.name');

        $this->applyFilters($query, $filters);

        return $query->simplePaginate(10)->withQueryString();
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

        $transactionTypes = [];
        foreach (TransactionTypeEnum::asArray() as $value) {
            $transactionTypes[] = ['label' => $value, 'value' => $value];
        }

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
        $reportCategory = ManagementReportCategoriesEnum::SALE_SUMMARY;
        $policyIssuanceDate = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'policyIssuanceDate' => $policyIssuanceDate,
            'reportCategory' => $reportCategory,
        ];
    }

    public function applyFilters($query, $filters)
    {
        if (isset($filters['policyIssuanceDate'])) {
            $query->whereBetween('policy_issuance_date', $filters['policyIssuanceDate']);
        }
        if (isset($filters['paymentDueDate'])) {
            $query->whereBetween('payments.payment_due_date', $filters['paymentDueDate']);
        }
        if (isset($filters['policyExpiredDate'])) {
            $query->whereBetween('policy_expired_date', $filters['policyExpiredDate']);
        }
        if (isset($filters['createdAt'])) {
            $query->whereBetween('created_at', $filters['createdAt']);
        }
        if (isset($filters['transactionType'])) {
            $query->where('transaction_type', $filters['transactionType']);
        }
        if (isset($filters['teams'])) {
            $query->whereIn('team_id', $filters['teams']);
        }
        if (isset($filters['subTeams'])) {
            $query->whereIn('sub_team_id', $filters['subTeams']);
        }
        if (isset($filters['leadSource'])) {
            $query->whereIn('lead_source', $filters['leadSource']);
        }
        if (isset($filters['includeCancelPolicies'])) {
            $query->where('is_cancelled', $filters['includeCancelPolicies']);
        }
        if (isset($filters['groupBy'])) {
            $query->groupBy($filters['groupBy']);
        }
        if (isset($filters['utmGroupBy'])) {
            $query->groupBy($filters['utmGroupBy']);
        }

    }
}
