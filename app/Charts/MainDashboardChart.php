<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class MainDashboardChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\BarChart
    {
        return $this->chart->barChart()
        ->setTitle('Comprehensive Conversion Report')
        ->addData('Batch 11', [13])
        ->addData('Batch 2', [5])
        ->addData('Batch 3', [7])
        ->addData('Batch 4', [7])
        ->addData('Batch 5', [7])
        ->addData('Batch 6', [7])
        ->addData('Batch 7', [7])
        ->setXAxis(['Batch 1', 'Batch 2', 'Batch 3', 'Batch 4', 'Batch 5', 'Batch 6']);
    }
}
