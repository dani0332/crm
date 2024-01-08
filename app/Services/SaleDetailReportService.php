<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportGroupByEnum;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;

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
                'COALESCE(payments.insurer_tax_number,payments.notes,payments.reference) as transactions',
                'policy_start_date',
                'policy_due_date',
                'source',
                'dummyteam as team',
                'price_vat_applicable',
                'payments.discount_value as discount',
                '((price_vat_applicable + price_vat_not_applicable + vat) - payments.discount_value) as total_price',
                'payments.commission_vat_applicable',
                'payments.commission_vat',
                'payments.commission_vat_not_applicable',
                '(commission_vat_applicable + commission_vat) as total_commission',
                "'collects' as collects",
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'payments.captured_at as date_paid',
                'premium_captured as collected_amount',
                "first_name + ' ' + last_name as customer_name",
                'customer_type as customer_type',
                "'customer_type' as customer_type",
                'quote_type.code as line_of_business',
                "'sub_type_line_of_business' as sub_type_line_of_business",
                'u.name as advisor_name',
                "pi.name as policy_issuer",
            )
            ->join('payments', 'personal_quotes.code', '=', 'payments.code')
            ->join('payment_status', 'payment_status.id', '=', 'payments.payment_status_id')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('users u', 'u.id', '=', 'advisor_id')
            ->join('users pi', 'pi.id', '=', 'policy_issuer_id')
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
            'advisor' => 'users.name',
            'line_of_business' => 'quote_type.code',
        ];

        return $mapping[$groupBy] ?? $groupBy;
    }

    public function getFilterOptions()
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

        $teamIds = $this->getUserTeams($loginUserId);

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

    public function getDefaultFilters()
    {
        $advisorAssignedDates = [ManagementReportCategoriesEnum::SALE_DETAIL];

        return [
            'managementReportCategories' => $advisorAssignedDates,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);
    }
}
