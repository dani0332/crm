<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Models\QuoteType;
use App\Models\Team;
use App\Models\Tier;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    protected $query;
    protected $searchPrefix = 'r.';

    public function generateBatchesFilterText()
    {
        $batchArray = [];
        $startDate = Carbon::parse('2018-08-05')->startOfYear();
        $count = 1;
        while ($startDate < now()) {
            $currentDate = $startDate->toDateString();
            $nextWeek = $startDate->addDays(7)->toDateString();
            $key = $currentDate.','.$nextWeek;
            $value = 'Batch - '.$count.' -('.$currentDate.'to'.$nextWeek.')';
            array_push($batchArray, $key.'|'.$value);
            $count++;
        }

        return $batchArray;
    }

    public function utmReport($request)
    {
        $records = [];
        if ($request->has('quote_type_id') && $request->has('group_by_one')) {
            $isGroupMedical = false;
            if ($request->quote_type_id == 999) {
                $request->quote_type_id = QuoteTypeId::Business; // group medical and business quote table is same
                $isGroupMedical = true;
            }

            $quoteTypeCode = QuoteType::where('id', '=', $request->quote_type_id)->value('code');
            $model = 'App\Models\\'.$quoteTypeCode.'Quote';
            $quoteRequestTable = strtolower($quoteTypeCode).'_quote_request';

            $groupByOne = $request->group_by_one;
            $groupByTwo = $request->group_by_two;
            $dateRange = $request->date_range;
            $groupBy[] = $groupByOne;
            if (! empty($groupByTwo)) {
                $groupBy[] = $groupByTwo;
            }

            $query = $model::query()->select(
                'utm_source',
                'utm_medium',
                'utm_campaign',
                DB::raw('COUNT('.$quoteRequestTable.'_detail.id) as leads_count'),
                DB::raw('COUNT(CASE  WHEN payment_status_id = '.PaymentStatusEnum::AUTHORISED.' THEN 1 ELSE NULL END) as authorized'),
                DB::raw('COUNT(CASE  WHEN payment_status_id = '.PaymentStatusEnum::CAPTURED.' THEN 1 ELSE NULL END) as captured'),
                DB::raw('sum(CASE WHEN payment_status_id = '.PaymentStatusEnum::AUTHORISED.' THEN premium  ELSE 0 END) as authorized_sum'),
                DB::raw('sum(CASE WHEN payment_status_id = '.PaymentStatusEnum::CAPTURED.' THEN premium  ELSE 0 END) as captured_sum'),
            )
                ->join($quoteRequestTable.'_detail', $quoteRequestTable.'.id', $quoteRequestTable.'_detail.'.$quoteRequestTable.'_id')->groupBy($groupBy)
                ->whereNotIn($quoteRequestTable.'.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);

            if ($isGroupMedical) {
                $query->where('business_type_of_insurance_id', QuoteTypeId::Business);
            }
            if (! empty($groupByOne)) {
                $query->where($groupByOne, '<>', '');
            }
            if (! empty($groupByTwo)) {
                $query->where($groupByTwo, '<>', '');
            }
            if (! empty($dateRange)) {
                $dateFrom = date('Y-m-d 00:00:00', strtotime($dateRange[0]));
                $dateTo = date('Y-m-d 23:59:59', strtotime($dateRange[1]));

                $query->whereBetween($quoteRequestTable.'.created_at', [$dateFrom, $dateTo]);
            }
            $records = $query->get();

            $records->map(function ($item) use ($groupBy) {
                $item['utm_source'] = in_array('utm_source', $groupBy) ? $item['utm_source'] : '';
                $item['utm_medium'] = in_array('utm_medium', $groupBy) ? $item['utm_medium'] : '';
                $item['utm_campaign'] = in_array('utm_campaign', $groupBy) ? $item['utm_campaign'] : '';

                return $item;
            });
        }

        $lobs = QuoteTypeRepository::whereIn('code', [quoteTypeCode::Car, quoteTypeCode::Home, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Pet, quoteTypeCode::Business])->get();
        $lobs->push([
            'id' => 999,
            'text' => 'Group Medical',
        ]);
        $lobs->all();

        $resp['records'] = $records;
        $resp['lobs'] = $lobs;

        return $resp;
    }

    public function getLeadsListReport($request)
    {
        $query = CarQuote::query()
            ->select(
                'users.id as advisor_id',
                'users.name as advisor',
                'car_quote_request.uuid as uuid',
                'tiers.name as tier',
                'car_quote_request.source as source',
                'car_quote_request.first_name as first_name',
                'car_quote_request.device as device',
                'car_quote_request.created_at as created_at',
                'car_quote_request.updated_at as updated_at',
                'car_quote_request.is_ecommerce as is_ecommerce',
                'quote_status.text as quoteStatus',
                'payment_status.text as payment_status_id',
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->join('payment_status', 'payment_status.id', 'car_quote_request.payment_status_id')
            ->join('quote_status', 'quote_status.id', 'car_quote_request.quote_status_id')
            ->orderBy('car_quote_request.created_at', 'desc');

        $filters = [
            'uuid' => $request->uuid,
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'ecommerceFilter' => $request->is_ecommerce,
            'paymentStatus' => $request->payment_status,
            'page' => $request->page,
        ];

        $query = $this->refineLeadsReportsWithFilters($query, $filters);

        return $query->simplePaginate(15)->withQueryString();
    }

    public function refineLeadsReportsWithFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) : ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);

        if (isset($filters->uuid) && is_string($filters->uuid)) {
            $query->where('car_quote_request.uuid', $filters->uuid);
        }

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiers));
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }

        if (isset($filters->teams) && count($filters->teams) > 0) {
            info('teamsFilter are : '.json_encode($filters->teams));
            $value = $filters->teams;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiersFilter));
            $query->whereIn('car_quote_request.tier_id', $filters->tiersFilter);
        }

        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            info('leadSourceFilter are : '.json_encode($filters->leadSourceFilter));
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        }

        if (isset($filters->paymentStatus) && count($filters->paymentStatus) > 0) {
            info('paymentStatus are : '.json_encode($filters->paymentStatus));
            $query->whereIn('car_quote_request.payment_status_id', $filters->paymentStatus);
        }

        if (isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All') {
            info('ecommerceFilter are : '.json_encode($filters->ecommerceFilter));
            $query->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }

        return $query;
    }

    public function getDefaultFiltersForLeadsList()
    {
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
        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSource = LeadSource::query()
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($source) => $source->name)
            ->toArray();
        $paymentStatus = PaymentStatus::query()
            ->orderBy('text')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($paymentStatus) => $paymentStatus->text)
            ->toArray();

        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'tiers' => $tiers,
            'teams' => $teams,
            'leadSource' => $leadSource,
            'paymentStatus' => $paymentStatus,
            'advisorAssignedDates' => $advisorAssignedDates,
        ];
    }

    public function getStaleLeadsReport($request, $includeStale = false)
    {
        $lob = $request->lob ?? QuoteTypes::HEALTH->value;
        $start = $request->date[0] ?? Carbon::now()->subDays(30)->format('Y-m-d H:i:s');
        $end = $request->date[1] ?? Carbon::now()->format('Y-m-d H:i:s');

        $hasTeam = $request->has('team') && $request->team !== '';
        $hasAdvisors = $request->has('advisors') && count($request->advisors) > 0;

        $totalOp = $request->filter_by === 'total_opportunity';

        if ($lob == QuoteTypes::PET->value || $lob == QuoteTypes::CYCLE->value || $lob == QuoteTypes::YACHT->value) {
            $pqs = [
                QuoteTypes::PET->value => QuoteTypeId::Pet,
                QuoteTypes::CYCLE->value => QuoteTypeId::Cycle,
                QuoteTypes::YACHT->value => QuoteTypeId::Yacht,
            ];

            $tableName = 'personal_quotes';
            $personalQuoteType = $pqs[$lob];

            $priceSum = $totalOp ? 'q.premium' : '1';

            $query = DB::table($tableName.' AS q')
                ->select(
                    'u.name AS team',
                    DB::raw(
                        '
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN '.$priceSum.' ELSE 0 END) AS new_lead,
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Allocated.' THEN '.$priceSum.' ELSE 0 END) AS allocated,
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Quoted.' THEN '.$priceSum.' ELSE 0 END) AS quoted,
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::FollowedUp.' THEN '.$priceSum.' ELSE 0 END) AS followed_up,
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::InNegotiation.' THEN '.$priceSum.' ELSE 0 END) AS in_negotiation,
                            SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::PaymentPending.' THEN '.$priceSum.' ELSE 0 END) AS payment_pending
                        '
                    ),
                )
                ->leftJoin('users AS u', 'u.id', '=', 'q.advisor_id')
                ->leftJoin('user_team AS ut', 'ut.user_id', '=', 'u.id')
                ->where('q.quote_type_id', $personalQuoteType)
                ->whereNotNull('q.advisor_id')
                ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                ->whereBetween('q.created_at', [$start, $end])
                ->groupBy('q.advisor_id');
        } else {
            $tableName = $lob === QuoteTypes::CORPLINE->value ? 'business_quote_request' : strtolower($lob).'_quote_request';

            $query = DB::table($tableName.' AS q')
                ->leftJoin('users AS u', 'u.id', '=', 'q.advisor_id')
                ->leftJoin('user_team AS ut', 'ut.user_id', '=', 'u.id')
                ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                ->whereNull('q.renewal_import_code')
                ->whereBetween('q.created_at', [$start, $end]);

            if ($lob == QuoteTypes::HEALTH->value) {
                $priceSum = $totalOp ? 'q.price_starting_from' : '1';

                $query->select(
                    $hasTeam ? 'u.name AS team' : 'q.health_team_type AS team',
                    DB::raw(
                        '
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN '.$priceSum.' ELSE 0 END) AS new_lead,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Allocated.' THEN '.$priceSum.' ELSE 0 END) AS allocated,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Quoted.' THEN '.$priceSum.' ELSE 0 END) AS quoted,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::FollowedUp.' THEN '.$priceSum.' ELSE 0 END) AS followed_up,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::InNegotiation.' THEN '.$priceSum.' ELSE 0 END) AS in_negotiation,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::PaymentPending.' THEN '.$priceSum.' ELSE 0 END) AS payment_pending,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::RenewalTermsReceived.' THEN '.$priceSum.' ELSE 0 END) AS renewal_terms_recevied,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::ApplicationPending.' THEN '.$priceSum.' ELSE 0 END) AS application_pending,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::ApplicationSubmitted.' THEN '.$priceSum.' ELSE 0 END) AS application_submitted,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::MissingDocumentsRequested.' THEN '.$priceSum.' ELSE 0 END) AS missing_documents
                        '
                    )
                )
                    ->whereNotNull('q.health_team_type')
                    ->groupBy($hasTeam ? 'q.advisor_id' : 'q.health_team_type');
            } elseif ($lob == QuoteTypes::HOME->value) {
                $priceSum = $totalOp ? 'q.premium' : '1';

                $query->select(
                    'u.name AS team',
                    DB::raw(
                        '
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN '.$priceSum.' ELSE 0 END) AS new_lead,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Allocated.' THEN '.$priceSum.' ELSE 0 END) AS allocated,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Quoted.' THEN '.$priceSum.' ELSE 0 END) AS quoted,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::FollowedUp.' THEN '.$priceSum.' ELSE 0 END) AS followed_up,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::InNegotiation.' THEN '.$priceSum.' ELSE 0 END) AS in_negotiation,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::PaymentPending.' THEN '.$priceSum.' ELSE 0 END) AS payment_pending
                        '
                    )
                )
                    ->whereNotNull('q.advisor_id')
                    ->groupBy('q.advisor_id');
            } elseif ($lob == QuoteTypes::CORPLINE->value) {
                $priceSum = $totalOp ? 'q.premium' : '1';
                $query->select(
                    'u.name AS team',
                    DB::raw(
                        '
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN '.$priceSum.' ELSE 0 END) AS new_lead,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Allocated.' THEN '.$priceSum.' ELSE 0 END) AS allocated,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::ProposalFormRequested.' THEN '.$priceSum.' ELSE 0 END) AS proposal_form_requested,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::ProposalFormReceived.' THEN '.$priceSum.' ELSE 0 END) AS proposal_form_received,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::PendingRenewalInformation.' THEN '.$priceSum.' ELSE 0 END) AS pending_renewal_information,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::AdditionalInformationRequested.' THEN '.$priceSum.' ELSE 0 END) AS additional_information_requested,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::QuoteRequested.' THEN '.$priceSum.' ELSE 0 END) AS quotes_requested,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::Quoted.' THEN '.$priceSum.' ELSE 0 END) AS quoted,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::FollowedUp.' THEN '.$priceSum.' ELSE 0 END) AS followed_up,
                        SUM(CASE WHEN q.quote_status_id = '.QuoteStatusEnum::FinalizingTerms.' THEN '.$priceSum.' ELSE 0 END) AS finalizing_terms
                        '
                    )
                )
                    ->whereNotNull('q.advisor_id')
                    ->groupBy('q.advisor_id');
            }
        }

        if ($includeStale) {
            $query->whereNotNull('q.stale_at');
        }

        if ($hasTeam) {
            $query->where('ut.team_id', $request->team);
        }

        if ($hasAdvisors) {
            $query->whereIn('q.advisor_id', $request->advisors);
        }

        if (isset($request->sortBy) && $request->sortBy !== '' && isset($request->sortType) && $request->sortType !== '') {
            $query->orderBy($request->sortBy, $request->sortType);
        } else {
            $query->orderBy('team', 'asc');
        }

        return $query;
    }

    public function getDefaultFiltersForTotalPremium()
    {
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
        $lobs = QuoteTypeRepository::whereIn('code', [quoteTypeCode::Car, quoteTypeCode::Home, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Pet, quoteTypeCode::Business])->get();

        return [
            'teams' => $teams,
            'quoteTypes' => $lobs,
        ];
    }

    public function totalPremiumReport($request)
    {
        // Set date range filter
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = $endDate = Carbon::now();

        if (isset($request->transaction_approved_dates)) {
            $startDate = Carbon::parse($request->transaction_approved_dates[0])->startOfDay()->format($dateFormat);
            $endDate = Carbon::parse($request->transaction_approved_dates[1])->endOfDay()->format($dateFormat);
        }

        // Initialize the query builder
        $totalPremiumQuery = DB::table('car_quote_request as cqr')
            ->select(
                DB::raw('"CAR" as quote_type_name'),
                DB::raw('DATE(cqr.transaction_approved_at) as transaction_date'),
                DB::raw('COALESCE(SUM(cqr.premium), 0) as total_premium'),
                'u.name as advisor_name'
            )
            ->join('users as u', 'cqr.advisor_id', '=', 'u.id')
            ->whereNotNull('cqr.advisor_id')
            ->whereBetween('cqr.transaction_approved_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(cqr.transaction_approved_at)'))
            ->orderBy(DB::raw('DATE(cqr.transaction_approved_at)'));

        // Apply team filter
        if (isset($request->teams) && count($request->teams) > 0) {
            $totalPremiumQuery->whereIn('cqr.advisor_id', function ($teamsSubQuery) use ($request) {
                $teamsSubQuery->select('ut.user_id')
                    ->from('user_team as ut')
                    ->join('teams as t', 'ut.team_id', '=', 't.id')
                    ->whereIn('t.id', $request->teams);
            });
        }

        // Apply userIds filter
        if (isset($request->userIds) && count($request->userIds) > 0) {
            $totalPremiumQuery->whereIn('cqr.advisor_id', $request->userIds);
        }

        // Execute the query and return the result
        return $totalPremiumQuery->get();
    }
}
