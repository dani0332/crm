<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\HealthQuote;
use App\Traits\CentralTrait;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteStatusEnum;

class HealthQuoteRepository extends BaseRepository
{
    use CentralTrait;
    public function model()
    {
        return HealthQuote::class;
    }
    public function fetchGetData($forExport = false, $forTotalLeadsCount = false)
    {
        $request = request();

        $sort_by = isset($request->sortBy) && $request->sortBy != '' ? $request->sortBy : 'created_at';
        $sort_type = isset($request->sortType) && $request->sortType != '' ? $request->sortType : 'desc';

        $query = $this->with([
            'advisor',
            'nationality',
            'insuranceProvider',
        ])
            ->filter(! $forExport, $forTotalLeadsCount);

            // if(!empty($request->advisors)){
            //     $query->whereIn('advisor_id', $request->advisors);
            // }

            
            // if (isset($request->quote_status) && is_array($request->quote_status) && count($request->quote_status) > 0) {
            //     $query->whereIn('quote_status_id', $request->quote_status);
            //     $query->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            // }

            // if (isset($request->sub_team) && $request->sub_team != '') {
            //     $query->where('health_team_type', $request->sub_team);
            // }

            // if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            //     $query->where('previous_quote_policy_number', $request->previous_quote_policy_number);
            // }

            // if (isset($request->is_renewal) && $request->is_renewal != '') {
            //     if ($request->is_renewal == quoteTypeCode::yesText) {
            //         $query->whereNotNull('previous_quote_policy_number');
            //     }
            //     if ($request->is_renewal == quoteTypeCode::noText) {
            //         $query->whereNull('previous_quote_policy_number');
            //     }
            // }

            // if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            //     $query->where('renewal_batch', $request->renewal_batch);
            // }

            // if (isset($request->is_ecommerce)) {
            //     $isEcommerce = $request->is_ecommerce == 'Yes' ? 1 : 0;
            //     $query->where('is_ecommerce', $isEcommerce);
            // }

            // if (isset($request->assignment_type) && ! empty($request->assignment_type)) {
            //     $query->where('assignment_type', $request->assignment_type);
            // }

            // if (! empty($request->assigned_to_date_start) && ! empty($request->assigned_to_date_end)) {
            //     $dateFrom = date('Y-m-d 00:00:00', strtotime($request['assigned_to_date_start']));
            //     $dateTo = date('Y-m-d 23:59:59', strtotime($request['assigned_to_date_end']));
    
            //     $query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            // }

            $query->withFakeLeadCriteria($forTotalLeadsCount)->orderBy($sort_by, $sort_type);

        if ($forTotalLeadsCount) {
            return $query->count();
        }

     
        return ($forExport) ? $query->get() : $query->Paginate();
    }

    public function fetchExport()
    {
        return $this->filter()->with(
            ['advisor', 'nationality', 'insuranceProvider'])->orderBy('created_at', 'desc');
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::HEALTH->value).'-quote', 'post', $dataArr);
    }
}
