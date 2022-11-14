<?php

namespace App\Services;

use Carbon\Carbon;

class ReportService extends BaseService
{
    protected $query;
    protected $searchPrefix = 'r.';
    public function __construct()
    {
    }

    public function getAdvisorConversionReportData()
    {
        $stats = "SELECT *
            FROM (
            SELECT
            count(q.id) total_leads,
            SUM(CASE WHEN q.quote_status_id = 40 THEN 1 ELSE 0 END) new_leads,
            SUM(CASE WHEN q.quote_status_id = 8 THEN 1 ELSE 0 END) not_interested,
            SUM(CASE WHEN q.quote_status_id in (2,24,25) THEN 1 ELSE 0 END) in_progress,
            SUM(CASE WHEN q.source = 'IMCRM' THEN 1 ELSE 0 END) manual_created,
            SUM(CASE WHEN q.quote_status_id in (9,35) THEN 1 ELSE 0 END) bad_leads,
            SUM(CASE WHEN q.quote_status_id = 33 THEN 1 ELSE 0 END) sale_leads,
            u.email
            FROM car_quote_request q
            INNER JOIN users u on u.id = q.advisor_id
            AND q.renewal_import_code IS NULL
            GROUP BY q.advisor_id)  a order by a.email";
    }

    public function generateBatchesFilterText()
    {
        $batchArray = [];
        $startDate = Carbon::parse('2018-08-05')->startOfYear();
        $count = 1;
        while ($startDate < now()) {
            $currentDate = $startDate->toDateString();
            $nextWeek = $startDate->addDays(7)->toDateString();
            $key = $currentDate.','.$nextWeek;
            $value = 'Batch - '.$count.' - ( '.$currentDate.' to '.$nextWeek.' )';
            array_push($batchArray, $key.'|'.$value);
            $count++;
        }

        return $batchArray;
    }
}
