<?php

namespace App\Exports;

use App\Services\Reports\RetentionReportService;
use App\Traits\ExcelExportable;
use Illuminate\Http\Request;

class RetentionReportExport
{
    use ExcelExportable;

    private $data;
    private $isShowBatchColumn;

    public function __construct(Request $request)
    {
        @[$this->data] = app(RetentionReportService::class)->getReportData($request, true);
        $this->isShowBatchColumn = app(RetentionReportService::class)->isShowBatchColumn($this->data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        $headers = [
            'Month',
            'Advisor Name',
            'Total',
            'Lost',
            'Invalid',
            'Sales',
            'Volume Gross Retention',
            'Volume Net Retention',
            'Relative Retention',
        ];

        if ($this->isShowBatchColumn) {
            $headers[] = 'Batch';
            $headers[] = 'Start Date';
            $headers[] = 'End Date';
        }

        return $headers;
    }

    public function map($report): array
    {
        $data = [
            $report->month,
            $report->advisor_name,
            $report->total,
            $report->lost,
            $report->invalid,
            $report->sales,
            $report->volume_gross_retention,
            $report->volume_net_retention,
            $report->relative_retention,
        ];

        if ($this->isShowBatchColumn) {
            $data[] = $report->batch;
            $data[] = $report->start_date;
            $data[] = $report->end_date;
        }

        return $data;
    }
}
