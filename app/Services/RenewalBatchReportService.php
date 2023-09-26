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
                'renewal_batches.month'
            )
            ->join('users', 'users.id', '=', 'car_quote_request.advisor_id')
            ->leftJoin('car_lost_quote_logs', function ($qry) {
                $qry->on('car_lost_quote_logs.car_quote_request_id', '=', 'car_quote_request.id')
                    ->whereRaw('car_lost_quote_logs.id IN (select MAX(clql.id) from car_lost_quote_logs as clql
                    join car_quote_request as cqr on cqr.id = clql.car_quote_request_id group by cqr.id)');
            })
            ->leftJoin('payments', function ($qry) {
                $qry->on('payments.paymentable_id', '=', 'car_quote_request.id')
                    ->whereIn('payments.payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED]);
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
        if ($authUserIsDeputyManager || $authUserIsManager || $authUserIsRenewalsManager) {
            if ($authUserIsDeputyManager) {
                $userIds = $this->deputyManagerWalkTree($authUserId);
            } else {
                $userIds = $this->walkTree($authUserId);
            }
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

        if ($$authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager
            && ! $authUserIsDeputyManager && ! $authUserIsCEO && ! $authUserIsAccounts) {
            $batches = $batches->whereHas('segmentAdvisors', function ($qry) use ($authUserId) {
                $qry->where('advisor_id', $authUserId);
            });
        }

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
         * get batches
         */
        $renewalBatches = RenewalBatch::select('name');
        if ($authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager
            && ! $authUserIsDeputyManager && ! $authUserIsCEO && ! $authUserIsAccounts) {
            $renewalBatches = $renewalBatches->whereHas('segmentAdvisors', function ($qry) use ($authUserId) {
                $qry->where('advisor_id', $authUserId);
            });
        }
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
        $combinedSegmentUserIds = array_unique(array_merge($volumeSegmentAdvisorsId, $valueSegmentAdvisorsId));
        $combinedSegmentUserIdsString = ! empty($combinedSegmentUserIds) ? implode(',', $combinedSegmentUserIds) : '0';

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
        if (! $authUserIsAdvisor && ! (isset($filters->advisors) && count($filters->advisors) > 0)) {
            $query->addSelect(
                DB::raw('count(DISTINCT car_quote_request.id) as total_allocated_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and payments.captured_at <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed'),

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

                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$combinedSegmentUserIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and payments.captured_at <= "'.$reportDateEnd.'" and car_quote_request.advisor_id = "'.$authUserId.'" THEN 1 ELSE 0 END) as renewed'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                    and payments.captured_at <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$combinedSegmentUserIdsString.') THEN 1 ELSE 0 END) as renewed_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id ='.$authUserId.'
                    THEN 1 ELSE 0 END) as car_sold'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                    and car_lost_quote_logs.status = "Approved"
                    and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                    and car_quote_request.advisor_id = '.$authUserId.'
                    THEN 1 ELSE 0 END) as uncontactable'),
            );
        }

        // advisor filter
        if (isset($filters->advisors) && count($filters->advisors) > 0) {
            $renewalBatches = $renewalBatches->whereHas('segmentAdvisors', function ($qry) use ($filters) {
                $qry->whereIn('advisor_id', $filters->advisors);
            });

            $advisors = ! empty($filters->advisors) ? implode(',', $filters->advisors) : '0';

            $query->addSelect(
                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$advisors.'), 1, 0)) as total_allocated_leads'),

                DB::raw('SUM(IF(car_quote_request.advisor_id in ('.$combinedSegmentUserIdsString.'), 1, 0)) as total_allocated_leads_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                        and payments.captured_at <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$advisors.') THEN 1 ELSE 0 END) as renewed'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                        and payments.captured_at <= "'.$reportDateEnd.'" and car_quote_request.advisor_id in ('.$combinedSegmentUserIdsString.') THEN 1 ELSE 0 END) as renewed_by_all_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::CarSold.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$advisors.')
                        THEN 1 ELSE 0 END) as car_sold'),

                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.quote_status_id = '.QuoteStatusEnum::Uncontactable.'
                        and car_lost_quote_logs.status = "Approved"
                        and car_lost_quote_logs.updated_at <="'.$reportDateEnd.'"
                        and car_quote_request.advisor_id in ('.$advisors.')
                        THEN 1 ELSE 0 END) as uncontactable'),
            );

            if ($authUserIsDeputyManager) {
                $userIds = $this->deputyManagerWalkTree($authUserId);
            } else {
                $userIds = $this->walkTree($authUserId);
            }

            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsDeputyManager || $authUserIsManager || $authUserIsRenewalsManager) {
            if ($authUserIsDeputyManager) {
                $userIds = $this->deputyManagerWalkTree($authUserId);
            } else {
                $userIds = $this->walkTree($authUserId);
            }
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        } elseif (! isset($filters->advisors) && $authUserIsAdvisor && ! $authUserIsManager && ! $authUserIsRenewalsManager) {
            $query->whereIn('car_quote_request.advisor_id', $combinedSegmentUserIds);
            // $query->where('car_quote_request.advisor_id', $authUserId);
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
        if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                and payments.captured_at <= "'.$reportDateEnd.'"  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
            );
        } elseif (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                and payments.captured_at <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                THEN 1 ELSE 0 END) as total_by_value_segment_advisors'),
            );
        } else {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                and payments.captured_at <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')
                THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),

                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id in ('.PaymentStatusEnum::CAPTURED.', '.PaymentStatusEnum::PARTIAL_CAPTURED.')
                and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')
                and payments.captured_at <= "'.$reportDateEnd.'" THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),

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

        //teams filter
        if (isset($filters->teams) && ($authUserIsCEO || $authUserIsAccounts)) {
            $query->join('user_team', 'user_team.user_id', '=', 'users.id');
            $teamsIds = $filters->teams;
            $query->whereIn('user_team.team_id', $teamsIds);
        }
        // subteams filter
        if (isset($filters->subTeams)) {
            $subTeamsIds = $filters->subTeams;
            $query->whereIn('users.sub_team_id', $subTeamsIds);
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
}
