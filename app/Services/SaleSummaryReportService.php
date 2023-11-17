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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleSummaryReportService implements ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        return PersonalQuote::query()
                    ->leftJoin('send_updates', 'personal_quotes.uuid', '=', 'send_updates.quote_uuid')
                    ->leftJoin('lookups', 'send_updates.type_id', '=', 'lookups.id')
                    ->select(
                        DB::raw('SUM(CASE WHEN COALESCE(policy_issuance_date, policy_number) IS NOT NULL THEN 1 ELSE 0 END) as total_policies'),
                        DB::raw('SUM(CASE WHEN send_updates.id IS NOT NULL AND lookups.code = "Financial" THEN 1 ELSE 0 END) as total_endorsements'),
                        DB::raw('SUM(CASE WHEN COALESCE(policy_issuance_date, policy_number) IS NOT NULL THEN 1 ELSE 0 END) +
                                SUM(CASE WHEN send_updates.id IS NOT NULL AND lookups.code = "Financial" THEN 1 ELSE 0 END) as total_transaction'),
                        DB::raw('SUM(CASE WHEN 1 THEN 1 ELSE 0 END) as total_vat')
                    )
                    ->get();


        // $typeCode = DB::raw('LOWER(quote_type.code)');
        // $dynamicTableName = DB::raw("CONCAT($typeCode, '_quote_request')");

        // $query->selectRaw("$dynamicTableName AS quote_table");

        // $query->join($dynamicTableName, function ($join) {
        //     $join->on('personal_quotes.uuid', '=', 'quote_table.uuid');
        // });

        // dd($query->toSql());
        // $result = $query->get();
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
        // implementation goes here
    }

    public function applyFilters($query, $filters)
    {
        // implementation goes here
    }
}
