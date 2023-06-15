<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\QuoteType;
use App\Repositories\QuoteTypeRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
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
                ->join($quoteRequestTable.'_detail', $quoteRequestTable.'.id', $quoteRequestTable.'_detail.'.$quoteRequestTable.'_id')->groupBy($groupBy);

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
            $records = $query->simplePaginate(10)->withQueryString();

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
}
