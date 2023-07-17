<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CarQuote;
use App\Enums\quoteTypeCode;
use App\Models\RenewalBatch;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Facades\DB;

class RenewalBatchReportService extends BaseService
{
    public function getReportData($request)
    {
        // $volumeSegmentAdvisorsId = User::whereHas('renewalBatch', function($qry){
        //     $qry->where('segment_type',  RenewalBatch::SEGMENT_TYPE_VOLUME);
        // })
        //     ->select('id')
        //     ->pluck('id')
        //     ->toArray();

        // $volumeSegmentAdvisorsIdString = implode(',', $volumeSegmentAdvisorsId);

        // $valueSegmentAdvisorsId = User::whereHas('renewalBatch', function($qry){
        //         $qry->where('segment_type',  RenewalBatch::SEGMENT_TYPE_VALUE);
        // })
        //     ->select('id')
        //     ->pluck('id')
        //     ->toArray();

        // $valueSegmentAdvisorsIdString = implode(',', $valueSegmentAdvisorsId);

        $query = CarQuote::query()
            ->select(
                'car_quote_request.renewal_batch',
                'renewal_batches.end_date',
                DB::raw('count(DISTINCT car_quote_request.id) as total_allocated_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'" THEN 1 ELSE 0 END) as renewed'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::CarSold.'" THEN 1 ELSE 0 END) as car_sold'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = "'.QuoteStatusEnum::uncontactable.'" THEN 1 ELSE 0 END) as uncontactable'),
            )
            ->join('users', 'users.id', '=', 'car_quote_request.advisor_id')
            ->join('renewal_batches', 'renewal_batches.id', '=', 'car_quote_request.renewal_batch')
            ->where('car_quote_request.source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.renewal_batch');
            // ->keyBy('car_quote_request.renewal_batch');

        // dump($query->get()->toArray());
        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    public function getFilterOptions()
    {
        $loginUserId = auth()->user()->id;

        $crudService = app()->make(CRUDService::class);

        $carAdvisors = $crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));

        $carAdvisors = $carAdvisors
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();


        $segments  = array_merge(['all'], RenewalBatch::SGEMENT_TYPES_LIST) ;

        return [
            'advisors'  =>  $carAdvisors,
            'segments'  =>  $segments
        ];
    }


    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        // $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        if ( isset($filters->reportDate) )
        {
            $reportDate = Carbon::parse($filters->reportDate)->startOfDay()->format($dateFormat) ;
            $query->where('renewal_batches.end_date', $reportDate);
        }

        $batchNo = isset($filters->batchNo) ? $filters->batchNo : null;

        if ($batchNo) {
            $query->where('renewal_batches.id', intval($batchNo));
        }else{
            $query->whereIn('car_quote_request.renewal_batch', ['2', '3']);
        }

        if (isset($filters->advisors) && count($filters->advisors) > 0) {
            $query->whereIn('car_quote_request.advisor_id', $filters->advisors);
        }

        $volumeSegmentAdvisorsId = User::whereHas('renewalBatch', function($qry){
            $qry->where('segment_type',  RenewalBatch::SEGMENT_TYPE_VOLUME);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $volumeSegmentAdvisorsIdString = implode(',', $volumeSegmentAdvisorsId);

        $valueSegmentAdvisorsId = User::whereHas('renewalBatch', function($qry){
                $qry->where('segment_type',  RenewalBatch::SEGMENT_TYPE_VALUE);
        })
            ->select('id')
            ->pluck('id')
            ->toArray();

        $valueSegmentAdvisorsIdString = implode(',', $valueSegmentAdvisorsId);

        // dd($volumeSegmentAdvisorsIdString, $valueSegmentAdvisorsIdString);

        if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VOLUME) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),
                // DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id != "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors')
            );
        }else if (isset($filters->segment) && $filters->segment === RenewalBatch::SEGMENT_TYPE_VALUE) {
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),
                // DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id != "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors')
            );
        }else{
            $query->addSelect(
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_volume_segment_advisors'),
                // DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id != "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$volumeSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_volume_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as renewed_by_value_segment_advisors'),
                // DB::raw('SUM(CASE WHEN car_quote_request.payment_status_id != "'.PaymentStatusEnum::CAPTURED.'" and car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors'),
                DB::raw('SUM(CASE WHEN car_quote_request.advisor_id in ('.$valueSegmentAdvisorsIdString.')  THEN 1 ELSE 0 END) as total_by_value_segment_advisors')
            );
        }

        // if (isset($filters->tiers) && count($filters->tiers) > 0) {
        //     info('tiersFilter are : '.json_encode($filters->tiers));
        //     $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        // }
        // if (isset($filters->teams) && count($filters->teams) > 0) {
        //     info('teamsFilter are : '.json_encode($filters->teams));
        //     $value = $filters->teams;
        //     $query->whereIn('users.id', function ($query) use ($value) {
        //         $query->distinct()
        //             ->select('users.id')
        //             ->from('users')
        //             ->join('user_team', 'user_team.user_id', 'users.id')
        //             ->join('teams', 'teams.id', 'user_team.team_id')
        //             ->whereIn('teams.id', $value);
        //     });
        // }

        // if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
        //     info('leadSourceFilter are : '.json_encode($filters->leadSourceFilter));
        //     $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        // }

        return $query;
    }
}
