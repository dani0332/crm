<?php

namespace App\Services;

use App\Enums\CarRegistrationType;
use App\Enums\DisplayByEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamsEnum;
use App\Enums\TeamTypeEnum;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteType;
use App\Models\Team;
use App\Services\Logger\LoggerService;
use App\Services\Reports\Reportable;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversionAsAtReportService extends BaseService
{
    use GetUserTreeTrait;
    use Reportable;
    use TeamHierarchyTrait;

    public function getReportQueryBuilder($request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        if ($request->lob && $request->startEndDate && $request->asAtDate) {

            if ($request->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
                $model = app(CarQuote::class);
                $detailModel = app(CarQuoteRequestDetail::class);
            } else {
                $model = app(PersonalQuote::class);
                $detailModel = app(PersonalQuoteDetail::class);
            }

            $alias = $model->getTable();
            $detailAlias = $detailModel->getTable();

            $saleLeadsCountQuery = strtr("
                SUM(
                    CASE WHEN (
                        (({$alias}.payment_status_id in (:paidStatuses) OR {$alias}.quote_status_id in (:approvedStatuses)) and {$alias}.transaction_approved_at is NULL) OR
                        ({$alias}.quote_status_id in (:approvedStatuses) and {$alias}.transaction_approved_at <= ':asAtDate')
                    ) THEN 1 ELSE 0 END) as sale_leads",
                [
                    ':approvedStatuses' => implode(',', [
                        QuoteStatusEnum::TransactionApproved,
                        QuoteStatusEnum::PolicyIssued,
                        QuoteStatusEnum::PolicyBooked,
                        QuoteStatusEnum::PolicySentToCustomer,
                    ]),
                    ':asAtDate' => Carbon::parse($request->asAtDate)->endOfDay()->format($dateFormat),
                    ':paidStatuses' => implode(',', $this->getPaidStatuses()),
                ]);

            $query = $model::query()
                ->select(
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw("SUM(CASE WHEN
                        {$alias}.quote_status_id in (".QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')
                        THEN 1 ELSE 0 END) as bad_leads'),
                    DB::raw($saleLeadsCountQuery),
                )
                ->join("{$detailAlias} as pqd", "{$alias}.id", "pqd.{$model->getForeignKey()}")
                ->join('quote_batches', 'quote_batches.id', "{$alias}.quote_batch_id")
                ->join('users', 'users.id', "{$alias}.advisor_id")
                ->where('users.is_active', true)
                ->whereNotIn("{$alias}.source", [
                    LeadSourceEnum::IMCRM,
                    LeadSourceEnum::INSLY,
                    LeadSourceEnum::RENEWAL_UPLOAD,
                    LeadSourceEnum::SAPGO,
                    LeadSourceEnum::SAPJO,
                ]);

            $filters = [
                'startEndDate' => $request->startEndDate,
                'lob' => $request->lob,
                'displayBy' => $request->displayBy,
                'tag' => $request->tag,
                'registration_type' => $request->registration_type,
                'vehicle_use' => $request->vehicle_use,
                'segment_filter' => $request->segment_filter,
                'page' => $request->page,
                'pec_flag' => $request->pec_flag,
            ];

            $this->applyFilters($query, $filters, $alias, $detailAlias, $model->getForeignKey());

            return $query;

        }
    }

    public function getReportData(Request $request)
    {

        $query = $this->getReportQueryBuilder($request);

        if (empty($query)) {
            return null;
        }

        LoggerService::sql(__CLASS__.' '.__FUNCTION__.': ', $query);

        // map operation to calculate gross and net conversions of records
        return $this->mapConversionData($query->get(), $request);
    }

    /**
     * Get the count of unassigned leads based on the request parameters.
     */
    public function getUnassignedLeadsCount(Request $request): int
    {
        $unassignedLeadsCount = 0;

        if ($request->lob && $request->createdAtDate) {
            $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

            $model = $request->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car)
                                    ? app(CarQuote::class)
                                    : app(PersonalQuote::class);

            $alias = $model->getTable();

            $createdAtDate = $request->createdAtDate;
            $startDate = Carbon::parse($createdAtDate[0])->startOfDay()->format($dateFormat);
            $endDate = Carbon::parse($createdAtDate[1])->endOfDay()->format($dateFormat);
            $leadSourcesToExclude = [
                LeadSourceEnum::REVIVAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
                LeadSourceEnum::AQEED_REVIVAL,
            ];

            $query = $model::query()
                ->whereNull("{$alias}.advisor_id")
                ->whereNotIn("{$alias}.source", $leadSourcesToExclude);

            if ($request->lob == QuoteTypes::getIdFromValue(quoteTypeCode::CORPLINE)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', "{$alias}.uuid")
                    ->where('business_quote_request.business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($request->lob == QuoteTypes::getIdFromValue(quoteTypeCode::GroupMedical)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', "{$alias}.uuid")
                    ->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($request->lob != QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
                $query->where("{$alias}.quote_type_id", $request->lob);
            }

            if ($request->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Health) && isset($request->pec_flag) && $request->pec_flag !== 'all') {
                $query->join('health_quote_request as hqr', 'hqr.uuid', "{$alias}.uuid");

                if ($request->pec_flag == '1') {
                    $query->whereNotNull('hqr.pec_marked_at');
                } else {
                    $query->whereNull('hqr.pec_marked_at');
                }
            }

            $unassignedLeadsCount = $query
                ->whereBetween("{$alias}.created_at", [$startDate, $endDate])
                ->count();
        }

        return $unassignedLeadsCount;
    }

    public function getFilterOptions()
    {
        $authUser = auth()->user();
        $loginUserId = $authUser->id;

        $userRoles = $authUser->roles->pluck('name')->toArray();
        $userProducts = $this->getUserProducts($loginUserId)->pluck('name')->toArray();

        $lobs = QuoteType::query()
            ->select('text', 'id')
            ->whereNotIn('code', [QuoteTypes::BUSINESS, QuoteTypes::CAR_BIKE])
            ->where('is_active', 1);

        if (! in_array(RolesEnum::SeniorManagement, $userRoles)) {
            $lobs->whereIn('code', $userProducts);
        }

        $lobs = $lobs->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(fn ($lob) => $lob->text)
            ->toArray();

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::SmartPhoneManager])) {
            /* Manually mapping because of quote_type & user_products names mismatch */
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::Device)] = TeamsEnum::DEVICE_INSURANCE->value;
        }

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::CorplineManager])) {
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::CORPLINE)] = quoteTypeCode::CORPLINE.' Insurance';
        }

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::GMManager])) {
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::GroupMedical)] = quoteTypeCode::GroupMedical.' Insurance';
        }

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::CyberManager]) || in_array('Cyber Insurance', $userProducts)) {
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::CYBER)] = quoteTypeCode::CYBER.' Insurance';
        }

        return [
            'lobs' => $lobs,
        ];
    }

    public function applyFilters($query, $filters, $alias, $detailAlias, $foreignKey)
    {
        $filters = (object) $filters;

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $startDate = (isset($filters->startEndDate)
        && is_array($filters->startEndDate)
        && ! empty($filters->startEndDate[0])
        ) ?
            Carbon::parse($filters->startEndDate[0])->startOfDay()->format($dateFormat) :
            Carbon::parse(now())->startOfDay()->format($dateFormat);

        $endDate = (isset($filters->startEndDate)
        && is_array($filters->startEndDate)
        && ! empty($filters->startEndDate[1])
        ) ?
            Carbon::parse($filters->startEndDate[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        if (isset($filters->startEndDate)) {
            $query->whereBetween('pqd.advisor_assigned_date', [$startDate, $endDate]);
        }

        if (isset($filters->lob)) {
            if ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::CORPLINE)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', "{$alias}.uuid");
                $query->where('business_quote_request.business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::GroupMedical)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', "{$alias}.uuid");
                $query->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($filters->lob != QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
                $query->where("{$alias}.quote_type_id", $filters->lob);
            }
        }
        if (isset($filters->tag)) {
            $query->join('quote_tags', 'quote_tags.quote_uuid', "{$alias}.uuid");
            if ($filters->tag == QuoteSegmentEnum::SIC->value) {
                $query->where('quote_tags.name', ucwords(QuoteSegmentEnum::SIC->value));
            } else {
                $query->whereIn('quote_tags.name', ['APUA', 'SPUA']);
            }
        }
        if (isset($filters->segment_filter)) {
            $tagName = QuoteSegmentEnum::FIC->tag();
            $query->join('quote_tags', 'quote_tags.quote_uuid', "{$alias}.uuid");
            $query->when($filters->segment_filter == QuoteSegmentEnum::FIC->value, function ($q) use ($tagName) {
                $q->where('quote_tags.name', $tagName);
            })->when($filters->segment_filter == QuoteSegmentEnum::NON_FIC->value, function ($q) use ($tagName) {
                $q->whereNotIn('quote_tags.name', [$tagName]);
            });
        }

        if (isset($filters->lob) && $filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
            if (isset($filters->registration_type) && $filters->registration_type != 'All') {
                $query->where("{$alias}.registration_type", $filters->registration_type);
            }

            if (isset($filters->vehicle_use) && $filters->vehicle_use != 'All' && isset($filters->registration_type) && $filters->registration_type == CarRegistrationType::COMPANY) {
                $query->where("{$alias}.vehicle_use", $filters->vehicle_use);
            }
        }

        if (isset($filters->lob) && $filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Health) && isset($filters->pec_flag) && $filters->pec_flag !== 'all') {
            if ($filters->pec_flag == '1') {
                $query->whereNotNull('hqr.pec_marked_at');
            } else {
                $query->whereNull('hqr.pec_marked_at');
            }
        }

        $this->applyBaseQueryToGroupBy($query, $filters, $alias);

        if (isset($filters->displayBy)) {
            switch ($filters->displayBy) {
                case DisplayByEnum::ADVISOR_NAME:
                    $this->getByAdvisorNameQuery($query, $filters, $alias);
                    break;
                case DisplayByEnum::SUBTEAM:
                    $this->getBySubTeamQuery($query, $alias);
                    break;
                case DisplayByEnum::LEADSOURCE:
                    $this->getByLeadSourceQuery($query, $filters, $alias);
                    break;
                case DisplayByEnum::EXTERNAL_LEADSOURCE:
                    $this->getByExternalLeadSourceQuery($query, $alias, $detailAlias, $foreignKey);
                    break;
                case DisplayByEnum::TIERS:
                    $this->getByTiersQuery($query, $alias);
                    break;
                case DisplayByEnum::NATIONALITY:
                    $this->getByNationalityQuery($query, $alias);
                    break;
                case DisplayByEnum::TEAM:
                    $this->getByTeamQuery($query, $filters, $alias);
                    break;
            }
        }

        return $query;
    }

    public function mapConversionData($query, $request)
    {
        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');

        $mappedData = $query->map(function ($row) use ($request, $dateFormat) {
            $netDenominator = $row->total_leads - $row->bad_leads;
            $grossDenominator = $row->total_leads;
            $row->net_conversion = (float) $netDenominator > 0 ? round(($row->sale_leads / $netDenominator) * 100, 2) : 0;
            $row->gross_conversion = (float) $grossDenominator > 0 ? round(($row->sale_leads / $grossDenominator) * 100, 2) : 0;
            $row->start_date = Carbon::parse($request->startEndDate[0])->format($dateFormat);
            $row->end_date = Carbon::parse($request->startEndDate[1])->format($dateFormat);
            $row->as_at_date = Carbon::parse($request->asAtDate)->format($dateFormat);

            return $row;
        });

        return $mappedData;
    }

    /**
     * update query with advisors name by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByAdvisorNameQuery($query, $filters, $alias)
    {
        return $query
            ->addSelect(
                'users.id as advisorId',
                'users.name as advisor_name'
            )
            ->whereNotNull("{$alias}.advisor_id")
            ->orderBy('advisor_name', 'asc')
            ->groupBy('advisorId');
    }

    /**
     * update query with sub teams by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getBySubTeamQuery($query, $alias)
    {
        return $query
            ->addSelect(
                'sub_teams.id as sub_team_id',
                'sub_teams.name as sub_team'
            )
            ->join('teams as sub_teams', 'users.sub_team_id', '=', 'sub_teams.id')
            ->where('sub_teams.type', TeamTypeEnum::SUB_TEAM)
            ->whereNotNull("{$alias}.advisor_id")
            ->orderBy('sub_team', 'asc')
            ->groupBy('sub_team_id');
    }

    /**
     * update query with lead source by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByLeadSourceQuery($query, $filters, $alias)
    {
        return $query
            ->addSelect(
                "{$alias}.source as lead_source"
            )
            ->whereNotNull("{$alias}.source")
            ->orderBy('lead_source', 'asc')
            ->groupBy('lead_source');
    }

    /**
     * update query with external lead source by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByExternalLeadSourceQuery($query, $alias, $detailAlias, $foreignKey)
    {
        return $query
            ->addSelect(
                "{$detailAlias}.utm_source as external_lead_source"
            )
            ->join($detailAlias, "{$alias}.id", "{$detailAlias}.{$foreignKey}")
            ->whereNotNull("{$detailAlias}.utm_source")
            ->orderBy('external_lead_source', 'asc')
            ->groupBy('external_lead_source');
    }

    /**
     * update query with tiers by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByTiersQuery($query, $alias)
    {
        return $query
            ->addSelect(
                't.name as tiers'
            )
            ->join('tiers as t', "{$alias}.tier_id", 't.id')
            ->whereNotNull("{$alias}.tier_id")
            ->orderBy('tiers', 'asc')
            ->groupBy('tiers');
    }

    /**
     * update query with nationality by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByNationalityQuery($query, $alias)
    {
        return $query
            ->addSelect(
                'n.text as nationality'
            )
            ->join('nationality as n', "{$alias}.nationality_id", 'n.id')
            ->whereNotNull("{$alias}.nationality_id")
            ->orderBy('nationality', 'asc')
            ->groupBy('nationality');
    }

    public function getByTeamQuery($query, $filters, $alias)
    {
        return $query
            ->addSelect(
                'teams.id as team_id',
                'teams.name as team'
            )
            ->where('teams.type', TeamTypeEnum::TEAM)
            ->whereNotNull("{$alias}.advisor_id")
            ->orderBy('teams.name', 'asc')
            ->groupBy('teams.id');
    }

    private function applyBaseQueryToGroupBy($query, $filters, $alias)
    {
        $parentTeamIds = $this->getParentTeamIds($filters);

        $isHealth = $filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Health);
        $isCar = $filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car);
        $isLife = $filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Life);

        if (! ($isCar || $isHealth || $isLife)) {
            return;
        }

        $query
            ->when($isHealth,
                function ($q) use ($alias) {
                    $q->join('health_quote_request as hqr', 'hqr.uuid', "{$alias}.uuid");
                    // Health uses `health_quote_request.health_team_type` as a team-name field.
                    // Since `teams.name` is not guaranteed unique across all team types,
                    // constrain the join to `Team` rows to avoid duplicate matches inflating COUNT(*).
                    $q->join('teams', 'teams.name', '=', 'hqr.health_team_type')
                        ->where('teams.type', TeamTypeEnum::TEAM);
                },
                function ($q) use ($alias) {
                    $q->join('user_team', 'user_team.user_id', "{$alias}.advisor_id");
                    $q->join('teams', 'teams.id', '=', 'user_team.team_id');
                }
            )
            ->whereNot('teams.name', 'like', '%'.TeamNameEnum::RENEWALS.'%')
            ->when(! empty($parentTeamIds), fn ($q) => $q->whereIn('teams.parent_team_id', $parentTeamIds));
    }

    private function getParentTeamIds($filters)
    {
        $parentTeam = null;

        if ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
            $parentTeam = TeamNameEnum::CAR;
        } elseif ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Health)) {
            $parentTeam = TeamNameEnum::HEALTH;
        } elseif ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Life)) {
            $parentTeam = TeamNameEnum::LIFE;
        }

        if (empty($parentTeam)) {
            return null;
        }

        $parentTeamIds = Team::where('name', $parentTeam)->where('type', TeamTypeEnum::PRODUCT)->pluck('id')->toArray();

        return $parentTeamIds;
    }

    /**
     * calculate total net conversion
     *
     * @param [type] $data
     * @return void
     */
    public function calculateTotalNetConversion($data)
    {
        $totalLeads = 0;
        $saleLeads = 0;
        $badLeads = 0;

        foreach ($data as $row) {
            $totalLeads += $row->total_leads;
            $saleLeads += $row->sale_leads;
            $badLeads += $row->bad_leads;
        }

        $numerator = $saleLeads;
        $denominator = $totalLeads - $badLeads;

        return $denominator > 0
            ? round(($numerator / $denominator) * 100, 2)
            : 'NaN';
    }

    /**
     * calculate total gross conversion
     *
     * @param [type] $data
     * @return void
     */
    public function calculateTotalGrossConversion($data)
    {
        $totalLeads = 0;
        $saleLeads = 0;

        foreach ($data as $row) {
            $totalLeads += $row->total_leads;
            $saleLeads += $row->sale_leads;
        }

        $numerator = $saleLeads;
        $denominator = $totalLeads;

        return $denominator > 0
            ? round(($numerator / $denominator) * 100, 2)
            : 'NaN';
    }
}
