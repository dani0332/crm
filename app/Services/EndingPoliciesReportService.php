<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndingPoliciesReportService implements ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {

        $query = PersonalQuote::query()
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('quote_type as qt', 'qt.id', '=', 'quote_type_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'insurance_provider_id')
            ->leftJoin('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'p.payment_status_id')
            ->leftJoin('customer as c', 'c.id', '=', 'customer_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->select(
                DB::raw('CONCAT(c.first_name, " ", c.last_name) as customer_name'),
                'policy_number',
                'ip.text as insurer',
                'qt.code as line_of_business',
                'policy_start_date',
                'p.policy_expiry_date as policy_end_date',
                DB::raw('SUM(premium) as collected_amount'),
                DB::raw('SUM(price_vat_applicable) as price_vat_applicable'),
                DB::raw('SUM(vat) as total_vat'),
                DB::raw('SUM(price_vat_not_applicable) as price_vat_not_applicable'),
                DB::raw('SUM(p.discount_value) as discount'),
                DB::raw('SUM(price_vat_applicable) + SUM(price_vat_not_applicable) + SUM(vat) - SUM(p.discount_value) as total_price'),
                DB::raw('(SUM(price_vat_applicable) + SUM(price_vat_not_applicable) + SUM(vat) - SUM(p.discount_value)) - SUM(premium) as pending_balance'),
                DB::raw('SUM(commission_vat_applicable) as commission_vat_applicable'),
                DB::raw('SUM(commission_vat) as commission_vat'),
                DB::raw('SUM(commission_vat_not_applicable) as commission_vat_not_applicable'),
                'pi.name as policy_issuer',
                'u.name as advisor',
                'personal_quotes.source',
                'personal_quotes.notes',
            )->when($request->groupBy, function ($query, $groupBy) {
                return $query->groupBy($this->resolveGroupByColumn($groupBy));
            });


        $this->applyFilters($query, $request);
        dd($query->toSql(),$query->getBindings());
        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'p.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'p.insurance_provider_id',
            'advisor' => 'u.name',
            'line_of_business' => 'qt.code',
        ];

        return $mapping[$groupBy] ?? $groupBy;
    }

    public function getFilterOptions()
    {

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

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
        ];
    }

    public function getDefaultFilters()
    {
        // implementation goes here
    }

    public function applyFilters($query, $request)
    {
        // implementation goes here
    }
}
