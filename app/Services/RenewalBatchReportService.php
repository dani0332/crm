<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\CarQuote;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
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
                DB::raw('MONTH(renewal_batches.end_date) month')
            )
            ->join('users', 'users.id', '=', 'car_quote_request.advisor_id')
            ->join('user_team', 'user_team.user_id', '=', 'users.id')
            ->join('renewal_batches', 'renewal_batches.name', '=', 'car_quote_request.renewal_batch')
            ->where('car_quote_request.source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.renewal_batch')
            ->orderBy('renewal_batches.end_date');

        $query = $this->applyFilters($query, $request->all());

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
        $authUserId = auth()->user()->id;

        /**
         * get auth user roles
         */
        $authUserIsManager = auth()->user()->hasRole(RolesEnum::CarManager);
        $authUserIsRenewalsManager = auth()->user()->hasRole(RolesEnum::RenewalsManager);
        $authUserIsDeputyManager = auth()->user()->hasRole(RolesEnum::CarDeputyManager);
        $authUserIsCEO = auth()->user()->hasRole(RolesEnum::SeniorManagement);
        $authUserIsAccounts = auth()->user()->hasRole(RolesEnum::Accounts);

        // get instance of crud service with the help of app service container
        $crudService = app()->make(CRUDService::class);
        // get car advisors
        $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));
        $carAdvisors = $carAdvisors
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        // filteration of valid advisores based on roles
        if ($authUserIsDeputyManager || $authUserIsManager || $authUserIsRenewalsManager) {
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

            $authUserTeams = Team::where('is_active', true)
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
            ->select('name', 'start_date', 'end_date', 'id')
            ->orderBy('id')
            ->get()
            ->keyBy('name')
            ->map(function ($batch) {
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                return $batch->name.'-('.$start_date.' to '.$end_date.')';
            })
            ->toArray();

        return [
            'advisors' => $carAdvisors,
            'segments' => $segments,
            'subTeams' => $authUserSubTeams,
            'teams' => $authUserTeams,
            'batches' => $batches,
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
        $renewalBatches = RenewalBatch::select('name')->pluck('name')->toArray();
        /**
         * check auth user roles
         */
        $authUserIsManager = auth()->user()->hasRole(RolesEnum::CarManager);
        $authUserIsRenewalsManager = auth()->user()->hasRole(RolesEnum::RenewalsManager);
        $authUserIsAdvisor = auth()->user()->hasRole(RolesEnum::CarAdvisor);
        $authUserIsDeputyManager = auth()->user()->hasRole(RolesEnum::CarDeputyManager);
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
         * query as per auth roles
         */
        if (! $authUserIsAdvisor) {
            $query->addSelect(
                DB::raw('count(DISTINCT car_quote_request.id) as total_allocated_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) THEN 1 ELSE 0 END) as renewed'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::CarSold.'" THEN 1 ELSE 0 END) as car_sold'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::Uncontactable.'" THEN 1 ELSE 0 END) as uncontactable'),
            );
        } elseif ($authUserIsAdvisor) {
            $query->addSelect(
                DB::raw('SUM(IF(car_quote_request.advisor_id = "'.$authUserId.'", 1, 0)) as total_allocated_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) and car_quote_request.advisor_id = "'.$authUserId.'" THEN 1 ELSE 0 END) as renewed'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::CarSold.'" and car_quote_request.advisor_id = "'.$authUserId.'" THEN 1 ELSE 0 END) as car_sold'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::Uncontactable.'" and car_quote_request.advisor_id = "'.$authUserId.'" THEN 1 ELSE 0 END) as uncontactable'),
            );
        }

        // date filter
        if (isset($filters->reportDate)) {
            $reportDate = Carbon::parse($filters->reportDate)->startOfDay()->format($dateFormat);
            $query->where('renewal_batches.end_date', $reportDate);
        }
        // else {
        //     $reportDate = Carbon::today()->format($dateFormat);
        // }
        // $query->where('renewal_batches.end_date', $reportDate);
        // batch no filter
        $batchNo = isset($filters->batchNo) ? $filters->batchNo : null;
        if ($batchNo) {
            $query->whereIn('car_quote_request.renewal_batch', $batchNo);
        } else {
            $query->whereIn('car_quote_request.renewal_batch', $renewalBatches);
        }
        // advisor filter
        if (isset($filters->advisors) && count($filters->advisors) > 0) {
            $query->whereIn('car_quote_request.advisor_id', $filters->advisors);
        } elseif (! isset($filters->advisors) && $authUserIsDeputyManager || $authUserIsManager || $authUserIsRenewalsManager) {
            $userIds = $this->walkTree($authUserId);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager) {
            $query->where('car_quote_request.advisor_id', $authUserId);
        }
        // segment wise advisors filter
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

        if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors')
            );
        } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors')
            );
        } else {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ( "'.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.'" ) and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors')
            );
        }
        //teams filter
        if (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts)) {
            $teamsIds = $filters->teams;
            $query->whereIn('user_team.team_id', $teamsIds);
        }
        // subteams filter
        if (isset($filters->subTeams)) {
            $subTeamsIds = $filters->subTeams;
            $query->whereIn('users.sub_team_id', $subTeamsIds);
        }
        // else if (!isset($filters->subTeams) && ( $authUserIsManager || $authUserIsRenewalsManager )&& !$authUserIsCEO && !$authUserIsAccounts) {
        //     $subTeamsIds = array_keys($authUserSubTeams);
        //     if(count($subTeamsIds) > 0) {
        //         $query->whereIn('users.sub_team_id', $subTeamsIds);
        //     }
        // }

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
