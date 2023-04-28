<?php

namespace App\Charts;

use App\Models\CarQuote;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use DB;

class ComprehensiveDashboard
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\BarChart
    {
        $records = CarQuote::query()
            ->select(
                'quote_batches.name',
                'quote_batches.start_date',
                'quote_batches.end_date',
                DB::raw('count(car_quote_request.id) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" and car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as created_sale_leads'),
            )
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->where('car_quote_request.source', '!=', 'IMCRM')
            ->groupBy('quote_batches.name', 'quote_batches.id')
            ->orderBy('quote_batches.id', 'desc')->take(10)->get();
        $chart = $this->chart->barChart()
            ->setTitle('Comprehensive Conversion Report');
        foreach ($records as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            $chart->addData($record->name.' ( '.$record->start_date.' to '.$record->end_date.' ) ', [$percentage.' %']);
        }
        $chart->setXAxis(['Net Conversion']);

        return $chart;
    }
}
