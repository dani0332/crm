<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\PaymentStatus;
use App\Models\QuoteType;
use App\Models\Team;
use App\Models\Tier;
use App\Repositories\QuoteTypeRepository;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
            }if (! empty($groupByOne)) {
                $query->where($groupByOne, '<>', '');
            }if (! empty($groupByTwo)) {
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
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

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

    public function getPaymentAuthorisedSummary($request)
    {
        $userRole = auth()->user();
        $userTeams = Auth::user()->getUserTeams(Auth::user()->id);

        if ($userRole->hasRole(RolesEnum::CarManager)) {

            $query = DB::table('car_quote_request');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(car_quote_request.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'car_quote_request.code')
                ->join('users', 'users.id', 'car_quote_request.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('car_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::HealthManager)) {
            $query = DB::table('health_quote_request');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(health_quote_request.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'health_quote_request.code')
                ->join('users', 'users.id', 'health_quote_request.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('health_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::BusinessManager)) {
            $query = DB::table('business_quote_request');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(business_quote_request.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'business_quote_request.code')
                ->join('users', 'users.id', 'business_quote_request.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('business_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::TravelManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Travel)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::HomeManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Home)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::PetManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Pet)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::YachtManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Yacht)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::LifeManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Life)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::BikeManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Bike)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::CycleManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Cycle)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        } elseif ($userRole->hasRole(RolesEnum::JetskiManager)) {
            $query = DB::table('personal_quotes');

            $query
                ->select(
                    'users.id as advisor_id',
                    'users.name as advisor_name',
                    DB::raw('COUNT(*) as total_leads'),
                    DB::raw('SUM(personal_quotes.premium) as total_premium'),
                    DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at')
                )
                ->leftJoin('payments as py', 'py.code', '=', 'personal_quotes.code')
                ->join('users', 'users.id', 'personal_quotes.advisor_id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                ->where('personal_quotes.payment_status_id', PaymentStatusEnum::AUTHORISED)
                ->where('personal_quotes.quote_type_id', QuoteTypeId::Jetski)
                ->whereIn('teams.name', $userTeams)
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_leads', 'desc');
        }

        // FILTERS
        if (isset($request->teams)) {
            $teamIds = $request->teams;
            $query->whereIn('users.id', function ($subQuery) use ($teamIds) {
                $subQuery
                    ->select('users.id')
                    ->distinct()
                    ->from('users')
                    ->join('user_team', 'users.id', '=', 'user_team.user_id')
                    ->join('teams', 'teams.id', '=', 'user_team.team_id')
                    ->whereIn('teams.id', $teamIds);
            });
        }
        if (isset($request->expireDate)) {
            $query->whereDate('py.authorized_at', '<=', $request->expireDate);
        }
        if (isset($request->todayDate)) {
            $query->whereBetween('py.authorized_at', $request->todayDate);
        }
        if (isset($request->tomorrowDate)) {
            $query->whereDate('py.authorized_at', '=', $request->tomorrowDate);
        }
        if (isset($request->thisWeek)) {
            $startOfWeek = $request->thisWeek[0];
            $endOfWeek = $request->thisWeek[1];

            $query->whereBetween('py.authorized_at', [$startOfWeek, $endOfWeek]);
        }
        if (isset($request->customDate)) {
            $query->whereBetween('py.authorized_at', $request->customDate);
        }

        if (! empty($query)) {
            return $query->simplePaginate(5)->withQueryString();

        }

        return false;

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
        $query = DB::table('personal_quotes');
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($request->page);
        $startDate = isset($request->transaction_approved_dates) ?
        Carbon::parse($request->transaction_approved_dates[0])->startOfDay()->format($dateFormat) :
            ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($request->transaction_approved_dates) ?
        Carbon::parse($request->transaction_approved_dates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('personal_quotes.transaction_approved_at', [$startDate, $endDate]);

        if (! empty($request->quote_type_id)) {
            $query->where('personal_quotes.quote_type_id', $request->quote_type_id);
        } else {
            $query->whereIn('personal_quotes.quote_type_id', [QuoteTypes::CAR->id()]);
        }

        if (isset($request->teams) && $request->filled('teams')) {
            $teamIds = $request->teams;
            $query->whereIn('users.id', function ($query) use ($teamIds) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $teamIds);
            });
        }

        $records = $query->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->join('users', 'personal_quotes.advisor_id', '=', 'users.id')
            ->select('quote_type.code as quote_type_name', DB::raw('DATE(personal_quotes.transaction_approved_at) as transaction_date'), DB::raw('COALESCE(SUM(personal_quotes.premium), 0) as total_premium'))
            ->groupBy(DB::raw('DATE(personal_quotes.transaction_approved_at)'))
            ->orderBy(DB::raw('DATE(personal_quotes.transaction_approved_at)'))
            ->get();

        return $records;
    }
}
