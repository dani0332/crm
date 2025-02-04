<?php

namespace App\Services\Reports;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Models\QuoteBatches;
use App\Models\QuoteType;
use App\Models\Team;
use App\Models\Tier;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService extends BaseService
{
    use GenericQueriesAllLobs;
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

    public function authorizedPaymentSummaryFilters()
    {

        $userRoles = auth()->user()?->getRoleNames()->toArray() ?? [];

        $quoteTypes = [
            QuoteTypes::CAR,
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::LIFE,
            QuoteTypes::BUSINESS,
            QuoteTypes::BIKE,
            QuoteTypes::YACHT,
            QuoteTypes::TRAVEL,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::JETSKI,
        ];

        $allowedLOBs = [];
        foreach ($quoteTypes as $quoteType) {
            if (
                in_array($quoteType->name.'_ADVISOR', $userRoles)
                || in_array($quoteType->name.'_MANAGER', $userRoles)
                || in_array(RolesEnum::Admin, $userRoles)
                || (auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct($quoteType))
            ) {
                $allowedLOBs[] = QuoteTypeRepository::where('code', $quoteType->value)->first();
            } else {
                continue;
            }
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

        return [
            'teams' => $teams,
            'quoteTypes' => $allowedLOBs,
        ];
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
        $lobs = QuoteTypeRepository::whereIn('code', [quoteTypeCode::Car, quoteTypeCode::Home, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Pet, quoteTypeCode::Business, quoteTypeCode::Cycle, quoteTypeCode::Bike, quoteTypeCode::Yacht])->get();

        return [
            'tiers' => $tiers,
            'teams' => $teams,
            'leadSource' => $leadSource,
            'paymentStatus' => $paymentStatus,
            'advisorAssignedDates' => $advisorAssignedDates,
            'quoteTypes' => $lobs,
        ];
    }

    public function getStaleLeadsReport($request, $includeStale = false)
    {
        $productIds = DB::table('user_products')->where('user_id', auth()->user()->id)->get()->pluck('product_id');
        $products = Team::whereIn('id', $productIds)->where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->get();

        $quoteTypes = [
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::YACHT,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::CORPLINE,
        ];

        $productsName = $products->pluck('name')->toArray();

        // Extract the 'value' properties from the QuoteTypes enumeration
        $quoteTypeValues = array_map(function ($quoteType) {
            return $quoteType->value;
        }, $quoteTypes);

        // Filter $productsName to include only those present in $quoteTypeValues
        $filteredProductsName = array_filter($productsName, function ($name) use ($quoteTypeValues) {
            return in_array($name, $quoteTypeValues);
        });

        // Re-index the filtered array to ensure consistent indexing
        $filteredProductsName = array_values($filteredProductsName);
        if ($filteredProductsName == null) {
            return abort(404);
        }
        $lob = $request->lob ?? $filteredProductsName[0];
        $start = $request->date[0] ?? Carbon::now()->subDays(90)->format('Y-m-d H:i:s');
        $end = $request->date[1] ?? Carbon::now()->format('Y-m-d H:i:s');

        $authUserId = auth()->user()->id;

        $hasTeam = $request->has('team') && $request->team !== '';
        $hasAdvisors = $request->has('advisors') && count($request->advisors) > 0;

        $totalOp = $request->filter_by === 'total_opportunity';

        if ($lob == QuoteTypes::PET->value || $lob == QuoteTypes::CYCLE->value || $lob == QuoteTypes::YACHT->value) {
            $pqs = [
                QuoteTypes::PET->value => QuoteTypeId::Pet,
                QuoteTypes::CYCLE->value => QuoteTypeId::Cycle,
                QuoteTypes::YACHT->value => QuoteTypeId::Yacht,
            ];

            $qtCode = [
                QuoteTypes::PET->value => quoteTypeCode::Pet,
                QuoteTypes::CYCLE->value => quoteTypeCode::Cycle,
                QuoteTypes::YACHT->value => quoteTypeCode::Yacht,
            ];

            $userIds = $this->walkTree($authUserId, $qtCode[$lob]);

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
                ->whereIn('q.advisor_id', $userIds)
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

                $userIds = $this->walkTree($authUserId, quoteTypeCode::Health);

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
                    ->whereIn('q.advisor_id', $userIds)
                    ->groupBy($hasTeam ? 'q.advisor_id' : 'q.health_team_type');
            } elseif ($lob == QuoteTypes::HOME->value) {
                $priceSum = $totalOp ? 'q.premium' : '1';

                $userIds = $this->walkTree($authUserId, quoteTypeCode::Home);

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
                    ->whereIn('q.advisor_id', $userIds)
                    ->groupBy('q.advisor_id');
            } elseif ($lob == QuoteTypes::CORPLINE->value) {
                $priceSum = $totalOp ? 'q.premium' : '1';
                $userIds = $this->walkTree($authUserId, quoteTypeCode::CORPLINE);
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
                    ->whereIn('q.advisor_id', $userIds)
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
        if ((! empty($request->teams)) && is_array($request->teams)) {
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

        $startTime = microtime(true);
        $result = $totalPremiumQuery->get();
        $endTime = microtime(true);

        info('totalPremiumQuery took '.number_format($endTime - $startTime, 4).' seconds to run');

        // dd($totalPremiumQuery->toSql(), $totalPremiumQuery->getBindings());
        // Execute the query and return the result
        return $result;
    }

    public function getPaymentAuthorisedSummary($request)
    {

        $user = auth()->user();
        $userTeams = $user->getUserTeams($user->id);
        $userRoles = auth()->user()?->getRoleNames()->toArray() ?? [];
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $expiryDays = $authorizedDays->value;

        $lobTable = [
            quoteTypeCode::Car => ['table' => 'car_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Home => ['table' => 'home_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Health => ['table' => 'health_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Business => ['table' => 'business_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Travel => ['table' => 'travel_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Life => ['table' => 'life_quote_request', 'quoteTypeId' => null],
            quoteTypeCode::Pet => ['table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Pet],
            quoteTypeCode::Yacht => ['table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Yacht],
            quoteTypeCode::Bike => ['table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Bike],
            quoteTypeCode::Cycle => ['table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Cycle],
            quoteTypeCode::Jetski => ['table' => 'personal_quotes', 'quoteTypeId' => QuoteTypeId::Jetski],
        ];

        $quoteTypes = [
            QuoteTypes::CAR,
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::LIFE,
            QuoteTypes::BUSINESS,
            QuoteTypes::BIKE,
            QuoteTypes::YACHT,
            QuoteTypes::TRAVEL,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::JETSKI,
        ];

        $allowedLOBs = [];
        if (isset($request->quoteType)) {
            $quoteType = explode(' ', Str::lower(trim($request->quoteType)))[0];
            $allowedLOBs[] = $lobTable[ucfirst($quoteType)];
        } else {
            $userRoles = auth()->user()?->getRoleNames()->toArray() ?? [];
            foreach ($quoteTypes as $quoteType) {
                if (in_array($quoteType->name.'_ADVISOR', $userRoles) || in_array($quoteType->name.'_MANAGER', $userRoles) || (auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct($quoteType))) {
                    $allowedLOBs[] = $lobTable[$quoteType->value];
                } elseif (in_array(RolesEnum::Admin, $userRoles)) {
                    $allowedLOBs[] = $lobTable[$quoteType->value];
                } else {
                    continue;
                }
            }
        }

        $dataCollection = collect();
        foreach ($allowedLOBs as $details) {
            $premiumColumn = $details['table'].'.premium';

            $query = DB::table($details['table'])
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    'quote_status_id',
                    DB::raw('COUNT(DISTINCT '.$details['table'].'.code) as total_leads'),
                    DB::raw('SUM('.$premiumColumn.') as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                    DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $expiryDays DAY), NOW()) as expiry_days")
                )
                ->leftJoin('payments as py', 'py.code', '=', $details['table'].'.code')
                ->join('users', 'users.id', $details['table'].'.advisor_id')
                ->when($details['quoteTypeId'] !== null, function ($query) use ($details) {
                    return $query->where($details['table'].'.quote_type_id', $details['quoteTypeId']);
                });
            $query->where('py.payment_status_id', PaymentStatusEnum::AUTHORISED);
            $query->where($details['table'].'.source', '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
            if ($user->isAdvisor()) {
                $query->where($details['table'].'.advisor_id', $user->id);
            } else {
                $query->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->whereIn('teams.name', $userTeams);
            }
            if (isset($request->userIds)) {
                $query->whereIn('advisor_id', $request->userIds);
            }
            if (isset($request->statusId)) {
                $query->whereIn('quote_status_id', $request->statusId);
            }

            if (isset($request->expireDate)) {
                $date = Carbon::parse($request->expireDate)->startOfDay();
                $query->whereDate(DB::raw('DATE_ADD(py.authorized_at, INTERVAL '.$expiryDays.' DAY)'), '<=', $date);
            }

            if (isset($request->todayDate)) {
                $query->where(DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $expiryDays DAY), NOW())"), '=', 1);
            }

            if (isset($request->tomorrowDate)) {
                $query->where(DB::raw("DATEDIFF(DATE_ADD(py.authorized_at, INTERVAL $expiryDays DAY), NOW())"), '=', 2);
            }

            if (isset($request->thisWeek)) {
                $startOfWeek = Carbon::parse($request->thisWeek[0])->startOfDay();
                $endOfWeek = Carbon::parse($request->thisWeek[1])->endOfDay();
                $query->whereBetween(DB::raw('DATE_ADD(py.authorized_at, INTERVAL '.$expiryDays.' DAY)'), [$startOfWeek, $endOfWeek]);
            }

            if (isset($request->customDate)) {
                $startDate = Carbon::parse($request->customDate[0])->startOfDay();
                $endDate = Carbon::parse($request->customDate[1])->endOfDay();
                $query->whereBetween(DB::raw('DATE_ADD(py.authorized_at, INTERVAL '.$expiryDays.' DAY)'), [$startDate, $endDate]);
            }

            $dataCollection = $dataCollection->merge($query->groupBy('users.id')
                ->orderBy('total_leads', 'desc')->get());
        }
        $items = $dataCollection->groupBy('advisor_id')->map(function ($group) {
            return [
                'advisor_id' => $group->first()->advisor_id,
                'advisor_name' => $group->first()->advisor_name,
                'total_premium' => $group->sum('total_premium'),
                'total_leads' => $group->sum('total_leads'),
            ];
        });

        $collection = collect($items)->groupBy('advisor_id')->flatten(1);
        $sorted = $collection->sortByDesc('total_leads');

        $result = $sorted->values()->all();

        $perPage = 5;
        $currentPage = (int) $request->input('page', 1);
        $total = count($result);
        $lastPage = ceil($total / $perPage);

        $paginatedData = array_slice($result, ($currentPage - 1) * $perPage, $perPage);

        $path = $request->fullUrl();

        $key = 'page';
        // Remove specific parameter from query string
        $path = preg_replace('~(\?|&)'.$key.'=[^&]*~', '$1', $path);

        $nextPageUrl = $currentPage < $lastPage
            ? $path.'?&page='.($currentPage + 1) : null;

        $prevPageUrl = $currentPage > 1
            ? $path.'?&page='.($currentPage - 1) : null;

        $pagination = [
            'data' => $paginatedData,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => ($currentPage - 1) * $perPage + 1,
            'to' => min($currentPage * $perPage, $total),
            'next_page_url' => $nextPageUrl,
            'prev_page_url' => $prevPageUrl,
        ];

        return $pagination;
    }

    public function getRevivalReportsData($request)
    {
        $source = [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID];

        $data = [];
        if (empty($request->lob)) {
            return $data;
        }
        $carInsurancetypeId = $request->car_type_insurance_id;
        $leadSource = $request->lead_source;

        if ($request->lob == QuoteTypeId::Health) {
            $model = HealthQuote::query();
            $tableName = 'health_quote_request';
            $query = $model
                ->select(
                    'dtt_revivals.revival_quote_batch_id as quote_batch_id',
                    DB::raw('COUNT(CASE  WHEN quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE NULL END) as transaction_approved'),
                    DB::raw('COUNT(CASE  WHEN email_sent = 1 THEN 1 ELSE NULL END) as email_sent_count'),
                    DB::raw('COUNT(CASE  WHEN reply_received = 1 THEN 1 ELSE NULL END) as reply_received_count'),
                )
                ->leftjoin('dtt_revivals', 'dtt_revivals.uuid', $tableName.'.uuid')
                ->whereNotNull(['dtt_revivals.revival_quote_batch_id'])
                ->orderBy('dtt_revivals.revival_quote_batch_id', 'desc');
            if (! empty($request->type_of_plan)) {
                $query->where('health_plan_type_id', $request->type_of_plan);
            }
        }

        if ($request->lob == QuoteTypeId::Car) {

            $model = CarQuote::query();
            $tableName = 'car_quote_request';
            $query = $model
                ->select(
                    'dtt_revivals.revival_quote_batch_id as quote_batch_id',
                    DB::raw('COUNT(CASE  WHEN payment_status_id = '.PaymentStatusEnum::CAPTURED.' THEN 1 ELSE NULL END) as conversion_captured'),
                    DB::raw('COUNT(CASE  WHEN source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE NULL END) as total_revived'),
                    DB::raw('COUNT(CASE  WHEN payment_status_id = '.PaymentStatusEnum::CAPTURED.' and  quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE NULL END) as captured'),
                    DB::raw('COUNT(CASE  WHEN payment_status_id = '.PaymentStatusEnum::AUTHORISED.' and  quote_status_id = '.QuoteStatusEnum::PaymentPending.' THEN 1 ELSE NULL END) as authorized'),

                    DB::raw('COUNT(CASE  WHEN email_sent = 1 THEN 1 ELSE NULL END) as email_sent_count'),
                    DB::raw('COUNT(CASE  WHEN reply_received = 1 THEN 1 ELSE NULL END) as reply_received_count'),
                )
                ->leftjoin('dtt_revivals', 'dtt_revivals.uuid', $tableName.'.uuid')
                ->whereNotNull(['dtt_revivals.revival_quote_batch_id'])
                ->orderBy('dtt_revivals.revival_quote_batch_id', 'desc');

            if (! empty($carInsurancetypeId)) {
                $query->where('car_type_insurance_id', $carInsurancetypeId);
            }
        }

        if (! empty($leadSource)) {
            $query->where('source', $leadSource);
        } else {
            $query->whereIn('source', $source);
        }
        $record = $query->groupBy('dtt_revivals.revival_quote_batch_id')->get()->toArray();

        if ($request->lob == QuoteTypeId::Health) {

            foreach ($record as $item) {

                $batch = QuoteBatches::find($item['quote_batch_id'])->name;
                // response rate of customer
                $rs['quote_batch_id'] = $batch;
                $rs['email_sent_count'] = $item['email_sent_count'];
                $rs['reply_received_count'] = $item['reply_received_count'];
                $rs['ratio'] = $item['email_sent_count'] > 0 ? round(($item['reply_received_count'] / $item['email_sent_count']) * 100, 2).'%' : null;
                $data['emailConversionReportHealth'][] = $rs;

                // transaction approved
                $rs['quote_batch_id'] = $batch;
                $rs['transaction_approved'] = $item['transaction_approved'];
                $rs['email_sent_count'] = $item['email_sent_count'];
                $rs['ratio'] = $item['transaction_approved'] > 0 ? round(($item['transaction_approved'] / $item['email_sent_count']) * 100, 2).'%' : null;
                $data['transactionApprovedReport'][] = $rs;
            }
        }
        if ($request->lob == QuoteTypeId::Car) {
            foreach ($record as $item) {

                // conversion rate
                $batch = QuoteBatches::find($item['quote_batch_id'])->name;
                $c['quote_batch_id'] = $batch;
                $c['conversion_captured'] = $item['conversion_captured'];
                $c['total_revived'] = $item['email_sent_count'];
                $c['ratio'] = $item['conversion_captured'] > 0 ? round(($item['conversion_captured'] / $item['email_sent_count']) * 100, 2).'%' : null;
                $data['conversionRate'][] = $c;

                // auth to capture
                $ac['quote_batch_id'] = $batch;
                $ac['authorized'] = $item['authorized'];
                $ac['captured'] = $item['captured'];
                $ac['ratio'] = $item['authorized'] > 0 ? round(($item['captured'] / $item['authorized']) * 100, 2).'%' : null;
                $data['leadConversionReport'][] = $ac;

                // response rate of customer
                $rs['quote_batch_id'] = $batch;
                $rs['email_sent_count'] = $item['email_sent_count'];
                $rs['reply_received_count'] = $item['reply_received_count'];
                $rs['ratio'] = $item['email_sent_count'] > 0 ? round(($item['reply_received_count'] / $item['email_sent_count']) * 100, 2).'%' : null;
                $data['emailConversionReportCar'][] = $rs;
            }
        }

        return $data;
    }
}
