<?php

namespace App\Services;

use App\Enums\DisplayByEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConversionAsAtReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        if ($request->lob && $request->startEndDate && $request->asAtDate) {
            $query = PersonalQuote::query()
                ->select(
                    DB::raw('SUM(CASE WHEN personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                    DB::raw('SUM(CASE WHEN
                        personal_quotes.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')
                        and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'"
                        and personal_quotes.transaction_approved_at <= "'.Carbon::parse($request->asAtDate)->endOfDay()->format($dateFormat).'"
                        THEN 1 ELSE 0 END) as bad_leads'),
                    DB::raw(
                        'SUM(
                            CASE WHEN (
                                ( personal_quotes.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"
                                and personal_quotes.payment_status_date <= "'.Carbon::parse($request->asAtDate)->endOfDay()->format($dateFormat).'"
                                )
                                OR
                                ( personal_quotes.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')
                                and personal_quotes.transaction_approved_at <= "'.Carbon::parse($request->asAtDate)->endOfDay()->format($dateFormat).'"
                                )
                            )
                          and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'"
                          THEN 1 ELSE 0 END) as sale_leads'
                    ),
                )
                ->join('personal_quote_details as pqd', 'personal_quotes.id', 'pqd.personal_quote_id')
                ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD);

            $filters = [
                'startEndDate' => $request->startEndDate,
                'lob' => $request->lob,
                'displayBy' => $request->displayBy,
                'page' => $request->page,
            ];

            $query = $this->applyFilters($query, $filters);

            $query = $query->get();

            // map operation to calculate gross and net conversions of records
            $mappedData = $this->mapConversionData($query, $request);

            return $mappedData;
        }
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

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::CorplineManager])) {
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::CORPLINE)] = quoteTypeCode::CORPLINE.' Insurance';
        }

        if ($authUser->hasAnyRole([RolesEnum::SeniorManagement, RolesEnum::GMManager])) {
            $lobs[QuoteTypes::getIdFromValue(quoteTypeCode::GroupMedical)] = quoteTypeCode::GroupMedical.' Insurance';
        }

        return [
            'lobs' => $lobs,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $startDate = isset($filters->startEndDate) ?
            Carbon::parse($filters->startEndDate[0])->startOfDay()->format($dateFormat) :
            Carbon::parse(now())->startOfDay()->format($dateFormat);

        $endDate = isset($filters->startEndDate) ?
            Carbon::parse($filters->startEndDate[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        if (isset($filters->startEndDate)) {
            $query->whereBetween('pqd.advisor_assigned_date', [$startDate, $endDate]);
        }

        if (isset($filters->lob)) {
            if ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::CORPLINE)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('business_quote_request.business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } elseif ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::GroupMedical)) {
                $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            } else {
                $query->where('personal_quotes.quote_type_id', $filters->lob);
            }
        }

        if (isset($filters->displayBy)) {
            switch ($filters->displayBy) {
                case DisplayByEnum::ADVISOR_NAME:
                    $this->getByAdvisorNameQuery($query);
                    break;
                case DisplayByEnum::SUBTEAM:
                    $this->getBySubTeamQuery($query);
                    break;
                case DisplayByEnum::LEADSOURCE:
                    $this->getByLeadSourceQuery($query);
                    break;
                case DisplayByEnum::EXTERNAL_LEADSOURCE:
                    $this->getByExternalLeadSourceQuery($query);
                    break;
                case DisplayByEnum::TIERS:
                    $this->getByTiersQuery($query);
                    break;
                case DisplayByEnum::NATIONALITY:
                    $this->getByNationalityQuery($query);
                    break;
                case DisplayByEnum::TEAM:
                    $this->getByTeamQuery($query, $filters);
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
    public function getByAdvisorNameQuery($query)
    {
        return $query
            ->addSelect(
                'users.id as advisorId',
                'users.name as advisor_name'
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->where('users.is_active', true)
            ->whereNotNull('personal_quotes.advisor_id')
            ->orderBy('advisor_name', 'asc')
            ->groupBy('advisorId');
    }

    /**
     * update query with sub teams by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getBySubTeamQuery($query)
    {
        return $query
            ->addSelect(
                'teams.id as sub_team_id',
                'teams.name as sub_team'
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('teams', 'users.sub_team_id', '=', 'teams.id')
            ->where('teams.type', TeamTypeEnum::SUB_TEAM)
            ->whereNotNull('personal_quotes.advisor_id')
            ->orderBy('sub_team', 'asc')
            ->groupBy('sub_team_id');
    }

    /**
     * update query with lead source by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByLeadSourceQuery($query)
    {
        return $query
            ->addSelect(
                'personal_quotes.source as lead_source'
            )
            ->whereNotNull('personal_quotes.source')
            ->orderBy('lead_source', 'asc')
            ->groupBy('lead_source');
    }

    /**
     * update query with external lead source by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByExternalLeadSourceQuery($query)
    {
        return $query
            ->addSelect(
                'personal_quote_details.utm_source as external_lead_source'
            )
            ->join('personal_quote_details', 'personal_quotes.id', 'personal_quote_details.personal_quote_id')
            ->whereNotNull('personal_quote_details.utm_source')
            ->orderBy('external_lead_source', 'asc')
            ->groupBy('external_lead_source');
    }

    /**
     * update query with tiers by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByTiersQuery($query)
    {
        return $query
            ->addSelect(
                't.name as tiers'
            )
            ->join('tiers as t', 'personal_quotes.tier_id', 't.id')
            ->whereNotNull('personal_quotes.tier_id')
            ->orderBy('tiers', 'asc')
            ->groupBy('tiers');
    }

    /**
     * update query with nationality by display function
     *
     * @param [type] $query
     * @return void
     */
    public function getByNationalityQuery($query)
    {
        return $query
            ->addSelect(
                'n.text as nationality'
            )
            ->join('nationality as n', 'personal_quotes.nationality_id', 'n.id')
            ->whereNotNull('personal_quotes.nationality_id')
            ->orderBy('nationality', 'asc')
            ->groupBy('nationality');
    }

    public function getByTeamQuery($query, $filters)
    {
        $teamType = null;
        if ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Car)) {
            $teamType = TeamTypeEnum::CAR_PARENT_TEAM_ID;
        } elseif ($filters->lob == QuoteTypes::getIdFromValue(quoteTypeCode::Health)) {
            $teamType = TeamTypeEnum::HEALTH_PARENT_TEAM_ID;
        }

        return $query
            ->addSelect(
                'teams.id as team_id',
                'teams.name as team'
            )
            ->join('user_team', 'user_team.user_id', 'personal_quotes.advisor_id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->where('teams.type', TeamTypeEnum::TEAM)
            ->where('teams.parent_team_id', $teamType)
            ->where('is_active', 1)
            ->whereNotNull('personal_quotes.advisor_id')
            ->orderBy('team', 'asc')
            ->groupBy('team_id');
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
