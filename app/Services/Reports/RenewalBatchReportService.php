<?php

namespace App\Services\Reports;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
use App\Services\BaseService;
use App\Services\CRUDService;
use App\Services\Query;
use App\Services\Request;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RenewalBatchReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    /**
     * get report data function
     *
     * @param  Request  $request
     * @return void
     */
    public function getReportData($request)
    {
        $query = CarQuote::query()
            ->select(
                'car_quote_request.renewal_batch',
                'renewal_batches.end_date',
                'renewal_batches.id',
                'renewal_batches.name',
                'renewal_batches.month'
            )
            ->join('users', 'users.id', '=', 'car_quote_request.advisor_id')
            ->leftJoin('car_lost_quote_logs', function ($qry) {
                $qry->on('car_lost_quote_logs.car_quote_request_id', '=', 'car_quote_request.id')
                    ->whereRaw('car_lost_quote_logs.id IN (select MAX(clql.id) from car_lost_quote_logs as clql
                    join car_quote_request as cqr on cqr.id = clql.car_quote_request_id group by cqr.id)');
            })
            ->join('renewal_batches', 'renewal_batches.name', '=', 'car_quote_request.renewal_batch')
            ->where('car_quote_request.source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.renewal_batch')
            ->orderBy('renewal_batches.end_date');

        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    /**
     * get super retention data function
     *
     * @param  Request  $request
     * @return void
     */
    public function getSuperRetentinoData($request)
    {
        $query = HealthQuote::query()
            ->select(
                'health_quote_request.renewal_batch',
                'health_quote_request.advisor_id',
                'health_quote_request.source',
                'health_quote_request.quote_status_id',
                'renewal_batches.end_date',
                'renewal_batches.id',
                'renewal_batches.name',
                'renewal_batches.month'
            )
            ->join('users', 'users.id', '=', 'health_quote_request.advisor_id')
            ->join('renewal_batches', 'renewal_batches.name', '=', 'health_quote_request.renewal_batch')
            ->where('health_quote_request.source', LeadSourceEnum::IMCRM)
            ->groupBy('health_quote_request.renewal_batch')
            ->orderBy('renewal_batches.end_date');

        $query = $this->applySuperRetentionFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    /**
     * get all available filters options function
     *
     * @return void
     */
    public function getFilterOptions()
    {
        $authUserTeams = null;
        $isBDM = false;
        $isMCR = false;
        $isRenewals = false;

        $authUserId = auth()->user()->id;

        /**
         * get auth user roles
         */
        $authUserIsManager = auth()->user()->hasRole(RolesEnum::CarManager);
        $authUserIsRenewalsManager = auth()->user()->hasRole(RolesEnum::RenewalsManager);
        $authUserIsCEO = auth()->user()->hasRole(RolesEnum::SeniorManagement);
        $authUserIsAccounts = auth()->user()->hasRole(RolesEnum::Accounts);
        $authUserIsAdvisor = auth()->user()->hasRole(RolesEnum::CarAdvisor);

        // get instance of crud service with the help of app service container
        $crudService = app()->make(CRUDService::class);
        // get car advisors
        $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));
        $carAdvisors = $carAdvisors
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        // filteration of valid advisores based on roles
        if ($authUserIsManager || $authUserIsRenewalsManager) {

            $userIds = $this->walkTree($authUserId);

            foreach ($carAdvisors as $key => $value) {
                if (! in_array($key, $userIds)) {
                    unset($carAdvisors[$key]);
                }
            }
        }
        // get all available segments list
        $segments = array_merge(['all'], RenewalBatch::SGEMENT_TYPES_LIST);
        //teams listing as per auth roles
        if ($authUserIsCEO || $authUserIsAccounts) {
            $authUserSubTeams = Team::where('is_active', true)
                ->where('type', TeamTypeEnum::SUB_TEAM)
                ->pluck('name', 'id')
                ->toArray();

            $car = $this->getProductByName(quoteTypeCode::Car);
            $authUserTeams = Team::where('is_active', true)
                ->where('parent_team_id', $car->id)
                ->where('type', TeamTypeEnum::TEAM)
                ->pluck('name', 'id')
                ->toArray();
        } else {
            $authUserTeamsIds = auth()->user()->getUserTeamsIds($authUserId)->toArray();

            $authUserSubTeams = $this->getSubTeamsByTeamIds($authUserTeamsIds)->toArray();

            $authUserSubTeams = array_reduce($authUserSubTeams, function ($carry, $item) {
                $carry[$item['id']] = $item['name'];

                return $carry;
            }, []);
        }

        $batches = RenewalBatch::query()
            ->select('name', 'start_date', 'end_date', 'id');

        $batches = $batches->orderBy('id')
            ->get()
            ->keyBy('name')
            ->map(function ($batch) {
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                return $batch->name.'-('.$start_date.' to '.$end_date.')';
            })
            ->toArray();

        $bdmTeamId = Team::where('name', TeamNameEnum::BDM)->select('id')->first()->id;
        if (in_array($bdmTeamId, $authUserTeamsIds)) {
            $isBDM = true;
        }

        $corpTeamId = Team::where('name', TeamNameEnum::MOTOR_COOPERATE_RENEWALS)->select('id')->first();
        if ($corpTeamId) {
            $corpTeamId = $corpTeamId->id;
            if (in_array($corpTeamId, $authUserTeamsIds)) {
                $isMCR = true;
            }
        }

        $renewalsTeamId = Team::where('name', TeamNameEnum::RENEWALS)->select('id')->first()->id;
        if (in_array($renewalsTeamId, $authUserTeamsIds)) {
            $isRenewals = true;
        }

        return [
            'advisors' => $carAdvisors,
            'segments' => $segments,
            'subTeams' => $authUserSubTeams,
            'teams' => $authUserTeams,
            'batches' => $batches,
            'isBDM' => $isBDM,
            'isMCR' => $isMCR,
            'isRenewals' => $isRenewals,
        ];
    }

    /**
     * apply all relevant filters function
     *
     * @param  Query  $query
     * @param  Request  $filters
     * @return void
     */
    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $authUserId = auth()->id();
        /**
         * check auth user roles
         */
        $authUserIsManager = auth()->user()->hasRole(RolesEnum::CarManager);
        $authUserIsRenewalsManager = auth()->user()->hasRole(RolesEnum::RenewalsManager);
        $authUserIsAdvisor = auth()->user()->hasRole(RolesEnum::CarAdvisor);
        $authUserIsCEO = auth()->user()->hasRole(RolesEnum::SeniorManagement);
        $authUserIsAccounts = auth()->user()->hasRole(RolesEnum::Accounts);
        /**
         * fetch teams and subteams
         */
        $authUserTeamsIds = auth()->user()->getUserTeamsIds($authUserId)->toArray();
        $authUserSubTeams = $this->getSubTeamsByTeamIds($authUserTeamsIds)->toArray();
        $authUserSubTeams = array_reduce($authUserSubTeams, function ($carry, $item) {
            $carry[$item['id']] = $item['name'];

            return $carry;
        }, []);

        /**
         * get batches
         */
        $renewalBatches = RenewalBatch::select('name');

        /**
         * Get volume and value segment advisors list
         */
        $volumeSegmentAdvisorsId = User::whereHas('renewalBatch', function ($qry) {
            $qry->where('segment_type', RenewalBatch::SEGMENT_TYPE_VOLUME);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $volumeSegmentAdvisorsIdString = ! empty($volumeSegmentAdvisorsId) ? implode(',', $volumeSegmentAdvisorsId) : '0';

        $valueSegmentAdvisorsId = User::whereHas('renewalBatch', function ($qry) {
            $qry->where('segment_type', RenewalBatch::SEGMENT_TYPE_VALUE);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $valueSegmentAdvisorsIdString = ! empty($valueSegmentAdvisorsId) ? implode(',', $valueSegmentAdvisorsId) : '0';

        // to be used in case of car advisor role
        $teamUsersIds = array_unique(array_merge($volumeSegmentAdvisorsId, $valueSegmentAdvisorsId));
        $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';

        /**
         * get whole team users
         */
        $bdmTeamId = Team::where('name', TeamNameEnum::BDM)->select('id')->first()->id;
        if (in_array($bdmTeamId, $authUserTeamsIds)) {
            $teamUsersIds = $this->getUsersByTeamIds([$bdmTeamId])->pluck('id')->toArray();
            $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
        }

        $sbdmTeamId = Team::where('name', TeamNameEnum::SBDM)->select('id')->first();
        if ($sbdmTeamId && in_array($sbdmTeamId->id, $authUserTeamsIds)) {
            $sbdmTeamId = $sbdmTeamId->id;
            $teamUsersIds = $this->getUsersByTeamIds([$sbdmTeamId])->pluck('id')->toArray();
            $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
        }

        $corpTeamId = Team::where('name', TeamNameEnum::MOTOR_COOPERATE_RENEWALS)->select('id')->first();
        if ($corpTeamId) {
            $corpTeamId = $corpTeamId->id;
            if (in_array($corpTeamId, $authUserTeamsIds)) {
                $teamUsersIds = $this->getUsersByTeamIds([$corpTeamId])->pluck('id')->toArray();
                $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
            }
        }

        $renewalsTeamId = Team::where('name', TeamNameEnum::RENEWALS)->select('id')->first()->id;
        if (in_array($renewalsTeamId, $authUserTeamsIds)) {
            $teamUsersIds = $this->getUsersByTeamIds([$renewalsTeamId])->pluck('id')->toArray();
            $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
        }

        // date filter
        if (isset($filters->reportDate)) {
            $reportDateEnd = Carbon::parse($filters->reportDate)
                ->endOfDay()->format($dateFormat);
        } else {
            $reportDateEnd = Carbon::today()->endOfDay()->format($dateFormat);
        }

        /**
         * query as per auth roles
         */
        if (! $authUserIsAdvisor && ! isset($filters->advisors) && ! isset($filters->subTeams) && ! isset($filters->teams)
            && (! isset($filters->segment) || $filters->segment === 'all')) {
            $query->addSelect(
                DB::raw('count(DISTINCT car_quote_request.id) as total_allocated_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as car_sold'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as uncontactable'),

            );
        } elseif ($authUserIsAdvisor) {
            $query->addSelect(
                DB::raw('SUM(IF(car_quote_request.advisor_id = "'.$authUserId.'", 1, 0)) as total_allocated_leads'),

                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$teamUsersIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" and car_quote_request.advisor_id = "'.$authUserId.'" THEN 1 ELSE 0 END) as renewed'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$teamUsersIdsString.') THEN 1 ELSE 0 END) as renewed_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id ='.$authUserId.'
                    THEN 1 ELSE 0 END) as car_sold'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id in ('.$teamUsersIdsString.')
                    THEN 1 ELSE 0 END) as car_sold_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id = '.$authUserId.'
                    THEN 1 ELSE 0 END) as uncontactable'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id in ('.$teamUsersIdsString.')
                    THEN 1 ELSE 0 END) as uncontactable_by_all_advisors'),

            );
        }

        // advisor filter
        if (! $authUserIsAdvisor &&
            (isset($filters->advisors) && count($filters->advisors) > 0)
            || (isset($filters->subTeams) && count($filters->subTeams) > 0)
            || (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts))
            || (isset($filters->segment) && $filters->segment != 'all')
        ) {

            $advisorsFilter = (isset($filters->advisors) ? $filters->advisors : []);

            if (isset($filters->subTeams) && count($filters->subTeams) > 0 && ! isset($filters->advisors)) {
                $subTeamsIds = $filters->subTeams;
                $advisorsFilter = $this->getUsersBySubTeamIds($subTeamsIds)->pluck('id')->toArray();
            }

            if (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts) && ! isset($filters->advisors)) {
                $teamsIds = $filters->teams;
                $advisorsFilter = $this->getUsersByTeamIds($teamsIds)->pluck('id')->toArray();
            }

            if ($authUserIsCEO || $authUserIsAccounts) {
                // get instance of crud service with the help of app service container
                $crudService = app()->make(CRUDService::class);
                // get car advisors
                $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));
                $userIds = $carAdvisors
                    ->map(fn ($users) => $users->id)
                    ->toArray();
            } else {
                $userIds = $this->walkTree($authUserId);
            }

            // segment wise advisors filter
            if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
                $query->addSelect(
                    DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    and car_quote_request.payment_status_date <= "'.$reportDateEnd.'"  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),

                    DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
                );
            } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
                $query->addSelect(
                    DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),

                    DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    THEN 1 ELSE 0 END) as total_by_value_segment_advisors'),
                );
            }

            $userIdsString = ! empty($userIds) ? implode(',', $userIds) : '0';

            $advisors = ! empty($advisorsFilter) ? implode(',', $advisorsFilter) : '0';

            $query->addSelect(
                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$advisors.'), 1, 0)) as total_allocated_leads'),

                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$userIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                        and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$advisors.') THEN 1 ELSE 0 END) as renewed'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                        and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$userIdsString.') THEN 1 ELSE 0 END) as renewed_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$advisors.')
                        THEN 1 ELSE 0 END) as car_sold'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$userIdsString.')
                        THEN 1 ELSE 0 END) as car_sold_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$advisors.')
                        THEN 1 ELSE 0 END) as uncontactable'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$userIdsString.')
                        THEN 1 ELSE 0 END) as uncontactable_by_all_advisors'),
            );

            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsManager || $authUserIsRenewalsManager) {
            $userIds = [];
            $userIds = $this->walkTree($authUserId);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager) {
            $query->whereIn('car_quote_request.advisor_id', array_merge([$authUserId], $teamUsersIds));
        }

        // batch no filter
        $batchNo = isset($filters->batchNo) ? $filters->batchNo : null;
        if ($batchNo) {
            $query->whereIn('car_quote_request.renewal_batch', $batchNo);
        } else {
            $renewalBatches = $renewalBatches->pluck('name')->toArray();
            $query->whereIn('car_quote_request.renewal_batch', $renewalBatches);
        }

        // segment wise advisors filter
        if (! isset($filters->segment) || $filters->segment === 'all') {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                and car_quote_request.payment_status_date <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                THEN 1 ELSE 0 END) as total_by_value_segment_advisors')
            );
        }

        /**
         * segment wise carsold and uncon
         */
        $query->addSelect(
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as car_sold_by_volume_segment'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as uncontactable_by_volume_segment'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as car_sold_by_value_segment'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as uncontactable_by_value_segment'),
        );

        $query->where('car_quote_request.created_at', '<=', $reportDateEnd);

        return $query;
    }

    /**
     * apply all relevant super retention filters function
     *
     * @param  Query  $query
     * @param  Request  $filters
     * @return void
     */
    public function applySuperRetentionFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $authUserId = auth()->id();
        /**
         * check auth user roles
         */
        $authUserIsManager = auth()->user()->hasRole(RolesEnum::CarManager);
        $authUserIsRenewalsManager = auth()->user()->hasRole(RolesEnum::RenewalsManager);
        $authUserIsAdvisor = auth()->user()->hasRole(RolesEnum::CarAdvisor);
        $authUserIsCEO = auth()->user()->hasRole(RolesEnum::SeniorManagement);
        $authUserIsAccounts = auth()->user()->hasRole(RolesEnum::Accounts);
        /**
         * fetch teams and subteams
         */
        $authUserTeamsIds = auth()->user()->getUserTeamsIds($authUserId)->toArray();
        $authUserSubTeams = $this->getSubTeamsByTeamIds($authUserTeamsIds)->toArray();
        $authUserSubTeams = array_reduce($authUserSubTeams, function ($carry, $item) {
            $carry[$item['id']] = $item['name'];

            return $carry;
        }, []);

        /**
         * get batches
         */
        $renewalBatches = RenewalBatch::select('name');
        $renewalBatches = $renewalBatches->pluck('name')->toArray();

        $renewalBatchesString = ! empty($renewalBatches) ? implode(',', $renewalBatches) : '0';

        /**
         * Get volume and value segment advisors list
         */
        $volumeSegmentAdvisorsId = User::whereHas('renewalBatch', function ($qry) {
            $qry->where('segment_type', RenewalBatch::SEGMENT_TYPE_VOLUME);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $volumeSegmentAdvisorsIdString = ! empty($volumeSegmentAdvisorsId) ? implode(',', $volumeSegmentAdvisorsId) : '0';

        $valueSegmentAdvisorsId = User::whereHas('renewalBatch', function ($qry) {
            $qry->where('segment_type', RenewalBatch::SEGMENT_TYPE_VALUE);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $valueSegmentAdvisorsIdString = ! empty($valueSegmentAdvisorsId) ? implode(',', $valueSegmentAdvisorsId) : '0';

        // to be used in case of car advisor role
        $teamUsersIds = array_unique(array_merge($volumeSegmentAdvisorsId, $valueSegmentAdvisorsId));
        $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';

        /**
         * get whole team users
         */
        $bdmTeamId = Team::where('name', TeamNameEnum::BDM)->select('id')->first()->id;
        if (in_array($bdmTeamId, $authUserTeamsIds)) {
            $teamUsersIds = $this->getUsersByTeamIds([$bdmTeamId])->pluck('id')->toArray();
            $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
        }

        $renewalsTeamId = Team::where('name', TeamNameEnum::RENEWALS)->select('id')->first()->id;
        if (in_array($renewalsTeamId, $authUserTeamsIds)) {
            $teamUsersIds = $this->getUsersByTeamIds([$renewalsTeamId])->pluck('id')->toArray();
            $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';
        }

        // date filter
        if (isset($filters->reportDate)) {
            $reportDateEnd = Carbon::parse($filters->reportDate)
                ->endOfDay()->format($dateFormat);
        } else {
            $reportDateEnd = Carbon::today()->endOfDay()->format($dateFormat);
        }

        /**
         * query as per auth roles
         */
        if (! $authUserIsAdvisor && ! isset($filters->advisors) && ! isset($filters->subTeams) && ! isset($filters->teams)
            && (! isset($filters->segment) || $filters->segment === 'all')) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN health_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.'
                    THEN 1 ELSE 0 END) as health_converted'),
            );
        } elseif ($authUserIsAdvisor) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN health_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.'
                    and health_quote_request.advisor_id = '.$authUserId.'
                    THEN 1 ELSE 0 END) as health_converted'),
            );
        }

        // advisor filter
        if (! $authUserIsAdvisor &&
            (isset($filters->advisors) && count($filters->advisors) > 0)
            || (isset($filters->subTeams) && count($filters->subTeams) > 0)
            || (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts))
            || (isset($filters->segment) && $filters->segment != 'all')
        ) {

            $advisorsFilter = (isset($filters->advisors) ? $filters->advisors : []);

            if (isset($filters->subTeams) && count($filters->subTeams) > 0 && ! isset($filters->advisors)) {
                $subTeamsIds = $filters->subTeams;
                $advisorsFilter = $this->getUsersBySubTeamIds($subTeamsIds)->pluck('id')->toArray();
            }

            if (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts) && ! isset($filters->advisors)) {
                $teamsIds = $filters->teams;
                $advisorsFilter = $this->getUsersByTeamIds($teamsIds)->pluck('id')->toArray();
            }

            if ($authUserIsCEO || $authUserIsAccounts) {
                // get instance of crud service with the help of app service container
                $crudService = app()->make(CRUDService::class);
                // get car advisors
                $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));
                $userIds = $carAdvisors
                    ->map(fn ($users) => $users->id)
                    ->toArray();
            } else {
                $userIds = $this->walkTree($authUserId);
            }

            // segment wise advisors filter
            if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
                $query->addSelect(
                    DB::raw('SUM(CASE WHEN health_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.'
                    and health_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    THEN 1 ELSE 0 END) as health_converted_by_volume_segment_advisors')
                );
            } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
                $query->addSelect(
                    DB::raw('SUM(CASE WHEN health_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.'
                    and health_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    THEN 1 ELSE 0 END) as health_converted_by_value_segment_advisors')
                );
            }

            $userIdsString = ! empty($userIds) ? implode(',', $userIds) : '0';

            $advisors = ! empty($advisorsFilter) ? implode(',', $advisorsFilter) : '0';

            $query->addSelect(
                DB::raw('SUM(CASE WHEN health_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.'
                    and health_quote_request.advisor_id in ('.$advisors.')
                    THEN 1 ELSE 0 END) as health_converted'),
            );

            $query = $query->whereIn('health_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsManager || $authUserIsRenewalsManager) {
            $userIds = [];
            $userIds = $this->walkTree($authUserId);
            $query = $query->whereIn('health_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager) {
            $query->whereIn('health_quote_request.advisor_id', array_merge([$authUserId], $teamUsersIds));
        }

        // batch no filter
        $batchNo = isset($filters->batchNo) ? $filters->batchNo : null;
        if ($batchNo) {
            $query->whereIn('health_quote_request.renewal_batch', $batchNo);
        } else {
            $query->whereIn('health_quote_request.renewal_batch', $renewalBatches);
        }

        $query->where('health_quote_request.created_at', '<=', $reportDateEnd);

        return $query;
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $reportDate = Carbon::today()->format($dateFormat);

        return [
            'reportDate' => $reportDate,
        ];
    }
}
