<?php

namespace App\Services\Reports;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\ApplicationStorage;
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
                'renewal_batches.month',
                'renewal_batches.year'
            )
            ->join('users', 'users.id', '=', 'car_quote_request.advisor_id')
            ->leftJoin('car_lost_quote_logs', function ($qry) {
                $qry->on('car_lost_quote_logs.car_quote_request_id', '=', 'car_quote_request.id')
                    ->whereRaw('car_lost_quote_logs.id IN (select MAX(clql.id) from car_lost_quote_logs as clql
                    join car_quote_request as cqr on cqr.id = clql.car_quote_request_id group by cqr.id)');
            })
            ->join('renewal_batches', 'renewal_batches.name', '=', 'car_quote_request.renewal_batch')
            ->where('car_quote_request.source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Duplicate, QuoteStatusEnum::PolicyCancelledReissued])
            ->groupBy('car_quote_request.renewal_batch')
            ->orderBy('renewal_batches.end_date');

        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)->withQueryString();
    }

    private function getM2ReleaseDate()
    {
        return cache()->remember('insly_m2_release_date', now()->addHour(), function () {
            return Carbon::parse(getAppStorageValueByKey(ApplicationStorageEnums::INSLY_M2_RELEASE_DATE));
        });
    }

    private function getCommonRenewedBindings($extra = [])
    {
        return [
            ':m2ReleaseDate' => $this->getM2ReleaseDate(),
            ':transactionApproved' => QuoteStatusEnum::TransactionApproved,
            ...$extra,
        ];
    }

    private function getRetentionRenewedBindings($reportDateEnd = null, $extra = [])
    {
        return $this->getCommonRenewedBindings(
            [
                ':reportDateEnd' => $reportDateEnd,
                ':retentionStatuses' => implode(',', [
                    QuoteStatusEnum::PolicyBooked,
                    QuoteStatusEnum::PolicyCancelled,
                ]),
                ...$extra,
            ]
        );
    }

    private function getSuperRetentionRenewedBindings($extra = [])
    {
        return $this->getCommonRenewedBindings(
            [
                ':superRetentionStatuses' => QuoteStatusEnum::PolicyBooked,
                ...$extra,
            ]
        );
    }

    /**
     * get super retention data function
     *
     * @param  Request  $request
     * @return void
     */
    public function getSuperRetentionData($request)
    {
        $ecommerceSource = ApplicationStorage::where('key_name', ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE)->value('value');
        $query = HealthQuote::query()
            ->select(
                'health_quote_request.renewal_batch',
                'health_quote_request.advisor_id',
                'health_quote_request.source',
                'health_quote_request.quote_status_id',
                'renewal_batches.end_date',
                'renewal_batches.id',
                'renewal_batches.name',
                'renewal_batches.month',
                'renewal_batches.year'
            )
            ->join('users', 'users.id', '=', 'health_quote_request.advisor_id')
            ->join('renewal_batches', 'renewal_batches.name', '=', 'health_quote_request.renewal_batch')
            ->where(function ($query) use ($ecommerceSource) {
                $query->where('health_quote_request.source', LeadSourceEnum::IMCRM)
                    ->orWhere('health_quote_request.source', 'like', '%'.$ecommerceSource.'%');
            })
            ->whereNotIn('health_quote_request.quote_status_id', [QuoteStatusEnum::Duplicate, QuoteStatusEnum::PolicyCancelledReissued])
            ->groupBy('health_quote_request.renewal_batch')
            ->orderBy('renewal_batches.end_date');

        $query = $this->applySuperRetentionFilters($query, $request->all());

        info(self::class.' - Super Retention Query Executed', [
            'query' => $query->toRawSql(),
        ]);

        return $query->get();
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
        [$authUserIsManager, $authUserIsRenewalsManager, $authUserIsCEO, $authUserIsAccounts, $authUserIsAdvisor]
            = $this->identifyUserRoles();

        /**
         * fetch teams and subteams
         */
        $authUserTeamsIds = auth()->user()->getUserTeamsIds($authUserId)->toArray();

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
        $car = $this->getProductByName(quoteTypeCode::Car);
        // teams listing as per auth roles
        if ($authUserIsCEO || $authUserIsAccounts) {
            $authUserSubTeams = Team::where('is_active', true)
                ->where('type', TeamTypeEnum::SUB_TEAM)
                ->pluck('name', 'id')
                ->toArray();

            $authUserTeams = Team::where('is_active', true)
                ->where('parent_team_id', $car->id)
                ->where('type', TeamTypeEnum::TEAM)
                ->pluck('name', 'id')
                ->toArray();
        } else {
            if ($authUserIsManager || $authUserIsRenewalsManager) {

                $allowedTeams = [
                    TeamNameEnum::RENEWALS,
                    TeamNameEnum::MOTOR_COOPERATE_RENEWALS,
                    TeamNameEnum::BDM,
                    TeamNameEnum::SBDM,
                    TeamNameEnum::PCP,
                    TeamNameEnum::ORGANIC,
                    TeamNameEnum::SIC_UNASSISTED,
                ];

                $authUserTeams = Team::where('is_active', true)
                    ->where('parent_team_id', $car->id)
                    ->where('type', TeamTypeEnum::TEAM)
                    ->whereIn('id', $authUserTeamsIds)
                    ->whereIn('name', $allowedTeams)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            $authUserSubTeams = $this->getSubTeamsByTeamIds($authUserTeamsIds)->toArray();

            $authUserSubTeams = array_reduce($authUserSubTeams, function ($carry, $item) {
                $carry[$item['id']] = $item['name'];

                return $carry;
            }, []);
        }

        $batches = RenewalBatch::query()
            ->select('name', 'start_date', 'end_date', 'id');

        $batches = $batches->orderBy('id')
            ->where('quote_type_id', QuoteTypes::CAR->id())
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
        [$authUserIsManager, $authUserIsRenewalsManager, $authUserIsCEO, $authUserIsAccounts, $authUserIsAdvisor]
            = $this->identifyUserRoles();
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
        $renewalBatches = RenewalBatch::select('name')->where('quote_type_id', QuoteTypeId::Car);

        // date filter
        if (isset($filters->reportDate)) {
            $reportDateEnd = Carbon::parse($filters->reportDate)->endOfDay()->format($dateFormat);

            $dataBatches = RenewalBatch::query()
                ->select('name', 'start_date', 'end_date', 'id')
                ->dateFilter($reportDateEnd, true)
                ->where('quote_type_id', QuoteTypeId::Car)
                ->orderByDesc('end_date')
                ->pluck('name', 'id')
                ->toArray();

        } else {
            //  get 3 months range batches
            $data = $this->getBatchRangeForDefaultView();
            $reportDateEnd = $nextMonth = $data['endDate'];
            $previousMonth = $data['startDate'];
            $dataBatches = $data['batches'];
        }

        /**
         * Get volume and value segment advisors list
         */
        $volumeSegmentAdvisorsId = $this->segmentWiseAdvisorsId(RenewalBatch::SEGMENT_TYPE_VOLUME);
        $volumeSegmentAdvisorsIdString = ! empty($volumeSegmentAdvisorsId) ? implode(',', $volumeSegmentAdvisorsId) : '0';

        $valueSegmentAdvisorsId = $this->segmentWiseAdvisorsId(RenewalBatch::SEGMENT_TYPE_VALUE);
        $valueSegmentAdvisorsIdString = ! empty($valueSegmentAdvisorsId) ? implode(',', $valueSegmentAdvisorsId) : '0';

        // to be used in case of car advisor role
        $teamUsersIds = array_unique(array_merge($volumeSegmentAdvisorsId, $valueSegmentAdvisorsId));
        $teamUsersIdsString = ! empty($teamUsersIds) ? implode(',', $teamUsersIds) : '0';

        // get batch wise segmented advisors
        $batchWiseSegmentedAdvisors = $this->batchwiseSegmentedAdvisors($dataBatches);

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

        /**
         * query as per auth roles
         */
        $nonAdvisorWithNoFilter = ! $authUserIsAdvisor && ! isset($filters->advisors) && ! isset($filters->subTeams) && ! isset($filters->teams)
            && (! isset($filters->segment) || $filters->segment === 'all');

        if ($nonAdvisorWithNoFilter) {
            $this->queryForNonAdvisorWithNoFilters($query, $reportDateEnd);
        } elseif ($authUserIsAdvisor) {
            $this->queryForAdvisor($query, $authUserId, $teamUsersIdsString, $reportDateEnd);
        }

        /**
         * apply filters
         */
        $nonAdvisorWithFilters = ! $authUserIsAdvisor && (isset($filters->advisors) && count($filters->advisors) > 0)
           || (isset($filters->subTeams) && count($filters->subTeams) > 0)
           || (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts))
           || (isset($filters->segment) && $filters->segment != 'all');

        if ($nonAdvisorWithFilters) {

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
                // get all car advisors
                $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));
                $userIds = $carAdvisors
                    ->map(fn ($users) => $users->id)
                    ->toArray();
            } else {
                $userIds = $this->walkTree($authUserId);
            }

            // segment wise advisors filter
            if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
                foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
                    $this->queryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VOLUME, $reportDateEnd,
                        'renewed_by_volume_segment_advisors', 'total_by_volume_segment_advisors', $batch);
                }
            } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
                foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
                    $this->queryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VALUE, $reportDateEnd,
                        'renewed_by_value_segment_advisors', 'total_by_value_segment_advisors', $batch);
                }
            }

            $userIdsString = ! empty($userIds) ? implode(',', $userIds) : '0';

            $advisors = ! empty($advisorsFilter) ? implode(',', $advisorsFilter) : '0';

            $this->queryForNonAdvisorWithFilters($query, $advisors, $userIdsString, $reportDateEnd);

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
            $query->whereIn('car_quote_request.renewal_batch', $dataBatches ?? $renewalBatches);
        }

        // segment wise advisors filter
        if (! isset($filters->segment) || $filters->segment === 'all') {

            foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
                $this->queryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VOLUME, $reportDateEnd,
                    'renewed_by_volume_segment_advisors', 'total_by_volume_segment_advisors', $batch);

                $this->queryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VALUE, $reportDateEnd,
                    'renewed_by_value_segment_advisors', 'total_by_value_segment_advisors', $batch);
            }
        }

        /**
         * segment wise carsold and early renewal
         */
        foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
            $this->queryForSegmentWiseCarsoldAndEarlyRenewal($query, $segmentedAdvisors[RenewalBatch::SEGMENT_TYPE_VOLUME], $segmentedAdvisors[RenewalBatch::SEGMENT_TYPE_VALUE], $reportDateEnd, $batch);
        }

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
        [$authUserIsManager, $authUserIsRenewalsManager, $authUserIsCEO, $authUserIsAccounts, $authUserIsAdvisor]
            = $this->identifyUserRoles();

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
        $renewalBatches = RenewalBatch::select('name')->where('quote_type_id', QuoteTypeId::Car);
        $renewalBatches = $renewalBatches->pluck('name')->toArray();

        // date filter
        if (isset($filters->reportDate)) {
            $reportDateEnd = Carbon::parse($filters->reportDate)->endOfDay()->format($dateFormat);

            $dataBatches = RenewalBatch::query()
                ->select('name', 'start_date', 'end_date', 'id')
                ->where('quote_type_id', QuoteTypeId::Car)
                ->dateFilter($reportDateEnd, true)
                ->orderByDesc('end_date')
                ->pluck('name', 'id')
                ->toArray();
        } else {
            //  get 3 months range batches
            $data = $this->getBatchRangeForDefaultView();
            $reportDateEnd = $nextMonth = $data['endDate'];
            $previousMonth = $data['startDate'];
            $dataBatches = $data['batches'];
        }

        /**
         * Get volume and value segment advisors list
         */
        $volumeSegmentAdvisorsId = $this->segmentWiseAdvisorsId(RenewalBatch::SEGMENT_TYPE_VOLUME);
        $volumeSegmentAdvisorsIdString = ! empty($volumeSegmentAdvisorsId) ? implode(',', $volumeSegmentAdvisorsId) : '0';

        $valueSegmentAdvisorsId = $this->segmentWiseAdvisorsId(RenewalBatch::SEGMENT_TYPE_VALUE);
        $valueSegmentAdvisorsIdString = ! empty($valueSegmentAdvisorsId) ? implode(',', $valueSegmentAdvisorsId) : '0';

        // to be used in case of car advisor role
        $teamUsersIds = array_unique(array_merge($volumeSegmentAdvisorsId, $valueSegmentAdvisorsId));

        // get batch wise segmented advisors
        $batchWiseSegmentedAdvisors = $this->batchwiseSegmentedAdvisors($dataBatches);
        /**
         * query as per auth roles
         */
        $nonAdvisorWithNoFilter = ! $authUserIsAdvisor && ! isset($filters->advisors) && ! isset($filters->subTeams) && ! isset($filters->teams)
            && (! isset($filters->segment) || $filters->segment === 'all');
        if ($nonAdvisorWithNoFilter) {
            $query->addSelect(
                DB::raw(
                    strtr(
                        'SUM(CASE WHEN (
                                    (health_quote_request.quote_status_id IN (:superRetentionStatuses) and health_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                                    (health_quote_request.quote_status_id IN (:transactionApproved) and health_quote_request.quote_status_date < ":m2ReleaseDate")
                                )
                                THEN 1 ELSE 0 END) AS health_converted',
                        $this->getSuperRetentionRenewedBindings()
                    )
                )
            );
        } elseif ($authUserIsAdvisor) {
            $query->addSelect(
                DB::raw(
                    strtr(
                        'SUM(CASE WHEN (
                                    (health_quote_request.quote_status_id IN (:superRetentionStatuses) AND health_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                                    (health_quote_request.quote_status_id IN (:transactionApproved) AND health_quote_request.quote_status_date < ":m2ReleaseDate")
                                )
                                AND health_quote_request.advisor_id = :authUserId
                                THEN 1 ELSE 0 END) AS health_converted',
                        $this->getSuperRetentionRenewedBindings([':authUserId' => $authUserId])
                    )
                )
            );
        }

        /**
         * apply filters
         */
        $nonAdvisorWithFilters = ! $authUserIsAdvisor && (isset($filters->advisors) && count($filters->advisors) > 0)
            || (isset($filters->subTeams) && count($filters->subTeams) > 0)
            || (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts))
            || (isset($filters->segment) && $filters->segment != 'all');

        if ($nonAdvisorWithFilters) {

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
                foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
                    $this->superRetentionQueryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VOLUME,
                        'health_converted_by_volume_segment_advisors', $batch);
                }
            } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
                foreach ($batchWiseSegmentedAdvisors as $batch => $segmentedAdvisors) {
                    $this->superRetentionQueryForSegmentType($query, $segmentedAdvisors, RenewalBatch::SEGMENT_TYPE_VALUE,
                        'health_converted_by_value_segment_advisors', $batch);
                }
            }

            $advisors = ! empty($advisorsFilter) ? implode(',', $advisorsFilter) : '0';

            $query->addSelect(
                DB::raw(
                    strtr(
                        'SUM(CASE WHEN (
                                    (health_quote_request.quote_status_id IN (:superRetentionStatuses) AND health_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                                    (health_quote_request.quote_status_id IN (:transactionApproved) AND health_quote_request.quote_status_date < ":m2ReleaseDate")
                                )
                                AND health_quote_request.advisor_id IN (:advisors)
                                THEN 1 ELSE 0 END) AS health_converted',
                        $this->getSuperRetentionRenewedBindings([':advisors' => $advisors])
                    )
                )
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
            $query->whereIn('health_quote_request.renewal_batch', $dataBatches ?? $renewalBatches);
        }

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

    /**
     * get batch ranges data for default report view function
     *
     * @return void
     */
    public function getBatchRangeForDefaultView()
    {
        $startDate = $endDate = null;

        $defaultBatchRange = RenewalBatch::query()
            ->select('name', 'start_date', 'end_date', 'id')
            ->where('quote_type_id', QuoteTypeId::Car)
            ->dateFilter()
            ->orderByDesc('end_date')
            ->get();

        if (empty($defaultBatchRange)) {
            $lastBatch = RenewalBatch::select('end_date')->where('quote_type_id', QuoteTypeId::Car)->orderByDesc('end_date')->first();
            $defaultBatchRange = RenewalBatch::query()
                ->select('name', 'start_date', 'end_date', 'id')
                ->where('quote_type_id', QuoteTypeId::Car)
                ->dateFilter($lastBatch->end_date)
                ->orderByDesc('end_date')
                ->get();
        }

        if (! empty($defaultBatchRange)) {
            $dateTimeFormat = config('constants.DB_DATE_FORMAT_MATCH');
            $startDate = Carbon::parse($defaultBatchRange->last()->start_date)->startOfDay()->format($dateTimeFormat);
            $endDate = Carbon::parse($defaultBatchRange->first()->end_date)->endOfDay()->format($dateTimeFormat);
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'batches' => $defaultBatchRange->pluck('name', 'id')->toArray(),
        ];
    }

    /**
     * get segment wise advisors id function
     *
     * @param [type] $segmentType
     * @return void
     */
    public function segmentWiseAdvisorsId($segmentType)
    {
        return User::whereHas('renewalBatch', function ($qry) use ($segmentType) {
            $qry->where('segment_type', $segmentType);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();
    }

    public function batchwiseSegmentedAdvisors($batches)
    {
        $data = [];
        foreach ($batches as $id => $batch) {
            $batchUsers = DB::table('renewal_batch_segment_user')
                ->select('id', 'advisor_id', 'renewal_batch_id', 'segment_type')
                ->where('renewal_batch_id', $id)
                ->get()
                ->toArray();

            // Grouping the records by 'segment_type'
            $groupedRecords = collect($batchUsers)->groupBy('segment_type');

            // Plucking 'advisor_id' and 'segment_type' from the grouped records
            $pluckedData = [];
            foreach ($groupedRecords as $segmentType => $segmentRecords) {
                $pluckedData[$segmentType] = $segmentRecords->pluck('advisor_id')->toArray();
            }

            $data[$batch] = $pluckedData;
        }

        return $data;
    }

    /**
     * update query for non advisor users in case of no filter is applied function
     *
     * @param [type] $query
     * @param [type] $reportDateEnd
     * @return void
     */
    public function queryForNonAdvisorWithNoFilters($query, $reportDateEnd)
    {
        return $query->addSelect(
            DB::raw('count(DISTINCT car_quote_request.id) as total_allocated_leads'),
            DB::raw(
                strtr(
                    'SUM(CASE WHEN (
                            (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                            (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                        ) THEN 1 ELSE 0 END) AS renewed',
                    $this->getRetentionRenewedBindings($reportDateEnd)
                )
            ),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                and car_lost_quote_logs.status = "Approved"
                and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                THEN 1 ELSE 0 END) as car_sold'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.status = "Approved"
                and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                THEN 1 ELSE 0 END) as early_renewal'),
        );
    }

    /**
     * update query for non advisor users in case of filters applied function
     *
     * @param [type] $query
     * @param [type] $advisors
     * @param [type] $userIdsString
     * @param [type] $reportDateEnd
     * @return void
     */
    public function queryForNonAdvisorWithFilters($query, $advisors, $userIdsString, $reportDateEnd)
    {
        return $query->addSelect(
            DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$advisors.'), 1, 0)) as total_allocated_leads'),

            DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$userIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

            DB::raw(
                strtr(
                    'SUM(CASE WHEN (
                            (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                            (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                        ) AND car_quote_request.advisor_id IN (:advisors)
                        THEN 1 ELSE 0 END) AS renewed',
                    $this->getRetentionRenewedBindings($reportDateEnd, [':advisors' => $advisors])
                )
            ),

            DB::raw(
                strtr(
                    'SUM(CASE WHEN (
                            (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                            (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                        ) AND car_quote_request.advisor_id IN (:userIds)
                        THEN 1 ELSE 0 END) AS renewed_by_all_advisors',
                    $this->getRetentionRenewedBindings($reportDateEnd, [':userIds' => $userIdsString])
                )
            ),

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

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id in ('.$advisors.')
                    THEN 1 ELSE 0 END) as early_renewal'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id in ('.$userIdsString.')
                    THEN 1 ELSE 0 END) as early_renewal_by_all_advisors'),
        );
    }

    /**
     * update query for advisor users function
     *
     * @param [type] $query
     * @param [type] $authUserId
     * @param [type] $teamUsersIdsString
     * @param [type] $reportDateEnd
     * @return void
     */
    public function queryForAdvisor($query, $authUserId, $teamUsersIdsString, $reportDateEnd)
    {
        return $query->addSelect(
            DB::raw('SUM(IF(car_quote_request.advisor_id = "'.$authUserId.'", 1, 0)) as total_allocated_leads'),

            DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$teamUsersIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

            DB::raw(
                strtr(
                    'SUM(CASE WHEN (
                            (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                            (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                        ) AND car_quote_request.advisor_id = :authUserId
                        THEN 1 ELSE 0 END) AS renewed',
                    $this->getRetentionRenewedBindings($reportDateEnd, [':authUserId' => $authUserId])
                )
            ),

            DB::raw(
                strtr(
                    'SUM(CASE WHEN (
                            (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                            (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                        ) AND car_quote_request.advisor_id IN (:teamUsersIds)
                        THEN 1 ELSE 0 END) AS renewed_by_all_advisors',
                    $this->getRetentionRenewedBindings($reportDateEnd, [':teamUsersIds' => $teamUsersIdsString])
                )
            ),

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

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.status = "Approved"
                and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                and car_quote_request.advisor_id = '.$authUserId.'
                THEN 1 ELSE 0 END) as early_renewal'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                and car_lost_quote_logs.status = "Approved"
                and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                and car_quote_request.advisor_id in ('.$teamUsersIdsString.')
                THEN 1 ELSE 0 END) as early_renewal_by_all_advisors'),
        );
    }

    /**
     * update query bases on segment type filter function
     *
     * @param [type] $query
     * @param [type] $segmentAdvisorsIdString
     * @param [type] $reportDateEnd
     * @param [type] $renewedAsColumn
     * @param [type] $totalAsColumn
     * @return void
     */
    public function queryForSegmentType($query, $batchWiseSegmentedAdvisors, $filter, $reportDateEnd, $renewedAsColumn, $totalAsColumn, $batchName = null)
    {
        if ($filter) {
            $advisors = is_array($batchWiseSegmentedAdvisors[$filter]) ? $batchWiseSegmentedAdvisors[$filter] : [$batchWiseSegmentedAdvisors[$filter]];
            $segmentAdvisorsIdString = implode(',', $advisors);

            return $query->addSelect(
                DB::raw(
                    strtr(
                        'SUM(CASE WHEN (
                                (car_quote_request.quote_status_id IN (:retentionStatuses) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                                (car_quote_request.quote_status_id IN (:transactionApproved) AND car_quote_request.quote_status_date <= ":reportDateEnd" AND car_quote_request.quote_status_date < ":m2ReleaseDate")
                            ) AND car_quote_request.advisor_id IN (:segmentAdvisors)
                            THEN 1 ELSE 0 END) AS ":as"',
                        $this->getRetentionRenewedBindings($reportDateEnd, [
                            ':segmentAdvisors' => $segmentAdvisorsIdString,
                            ':as' => "{$renewedAsColumn}_for_{$batchName}",
                        ])
                    )
                ),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$segmentAdvisorsIdString.')
                    and car_quote_request.renewal_batch = "'.$batchName.'"
                    THEN 1 ELSE 0 END) as "'.$totalAsColumn.'_for_'.$batchName.'"'),
            );
        }
    }

    public function superRetentionQueryForSegmentType($query, $batchWiseSegmentedAdvisors, $filter, $renewedAsColumn, $batchName = null)
    {
        if ($filter) {
            $advisors = is_array($batchWiseSegmentedAdvisors[$filter]) ? $batchWiseSegmentedAdvisors[$filter] : [$batchWiseSegmentedAdvisors[$filter]];
            $segmentAdvisorsIdString = implode(',', $advisors);

            return $query->addSelect(
                DB::raw(
                    strtr('SUM(CASE WHEN (
                                (health_quote_request.quote_status_id IN (:superRetentionStatuses) and health_quote_request.quote_status_date >= ":m2ReleaseDate") OR
                                (health_quote_request.quote_status_id IN (:transactionApproved) and health_quote_request.quote_status_date < ":m2ReleaseDate")
                            )
                            AND health_quote_request.advisor_id IN (:advisorId)
                            THEN 1 ELSE 0 END) AS ":as"',
                        $this->getSuperRetentionRenewedBindings([
                            ':advisorId' => $segmentAdvisorsIdString,
                            ':as' => "{$renewedAsColumn}_for_{$batchName}",
                        ])
                    )
                )
            );
        }
    }

    /**
     * update query for segment wise car sold and early renewals function
     *
     * @param [type] $query
     * @param [type] $volumeSegmentAdvisorsIdString
     * @param [type] $valueSegmentAdvisorsIdString
     * @param [type] $reportDateEnd
     * @return void
     */
    public function queryForSegmentWiseCarsoldAndEarlyRenewal($query, $volumeSegmentAdvisors, $valueSegmentAdvisors, $reportDateEnd, $batch)
    {
        $volumeSegmentAdvisorsIdString = implode(',', $volumeSegmentAdvisors);
        $valueSegmentAdvisorsIdString = implode(',', $valueSegmentAdvisors);

        return $query->addSelect(
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as "car_sold_by_volume_segment_for_'.$batch.'"'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as "early_renewal_by_volume_segment_for_'.$batch.'"'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as "car_sold_by_value_segment_for_'.$batch.'"'),

            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::EarlyRenewal.'
                    and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    THEN 1 ELSE 0 END) as "early_renewal_by_value_segment_for_'.$batch.'"'),
        );
    }

    public function identifyUserRoles()
    {
        /**
         * get auth user roles
         */
        $authUserRoles = auth()->user()->roles->pluck('name')->toArray();

        $authUserIsManager = in_array(RolesEnum::CarManager, $authUserRoles);
        $authUserIsRenewalsManager = in_array(RolesEnum::RenewalsManager, $authUserRoles);
        $authUserIsCEO = in_array(RolesEnum::SeniorManagement, $authUserRoles);
        $authUserIsAccounts = in_array(RolesEnum::Accounts, $authUserRoles);
        $authUserIsAdvisor = in_array(RolesEnum::CarAdvisor, $authUserRoles);

        return [
            $authUserIsManager,
            $authUserIsRenewalsManager,
            $authUserIsCEO,
            $authUserIsAccounts,
            $authUserIsAdvisor,
        ];

    }

    public function getAllNonMotorBatches()
    {
        $renewalBatches = RenewalBatch::select('id', 'name', 'start_date', 'end_date', 'month', 'year')->whereNull('quote_type_id');
        $renewalBatches->orderBy('id');
        $renewalBatches = $renewalBatches->get()
            ->map(function ($batch) {
                // Get the date display format from the configuration
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                // Format the start and end dates of the batch
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                // Return an associative array with the batch 'name' and 'id'
                return [
                    'id' => $batch->id,
                    'name' => "{$batch->month_name}-{$batch->name}-({$start_date} to {$end_date})",
                ];
            })
            ->toArray();

        return $renewalBatches;
    }
}
