<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use App\Models\HealthQuote;
use App\Services\BaseService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;
use App\Traits\GenericQueriesAllLobs;
use DateTime;

class RetentionReportService extends BaseService
{
    use TeamHierarchyTrait, GenericQueriesAllLobs;

    public function getReportData($request)
    {
        $quoteType = '';
        if (isset($request->lob)) {
            $quoteType = $request->lob;
        } else {
            $quoteType = $this->getUserPorductName(); // Corrected method name
        }

        if ($quoteType == '') {
            return [];
        }

        $quoteModel = $this->getModelObject($quoteType);
        if (!$quoteModel) {
            return [];
        }

        $query = $quoteModel::query()
            ->selectRaw("MONTHNAME(policy_expiry_date) as `month`,
                users.name as `advisor_name`,
                SUM(CASE WHEN source = 'Renewal_upload' THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN quote_status_id = 17 and source = 'Renewal_upload' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN quote_status_id IN (9, 35) and source = 'Renewal_upload' THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN quote_status_id = 56 and source = 'Renewal_upload' THEN 1 ELSE 0 END) as sales")
                ->join('users', 'advisor_id', '=', 'users.id');

        $this->applyFilters($query, $request->all());

        if ($request->displayBy == 'batch') {
            $this->applyFilterForBatch($query, $request);
        } else if ($request->displayBy == 'month'){
            $this->applyFilterByMonth($query, $request);
        }

        $reportData = $query->paginate(12)->withQueryString();

        foreach ($reportData as $report) {
            $sales = $report->sales;
            $invalid = $report->invalid;
            $total = $report->total;

            $totalInvalid = $total - $invalid;

            $volumeNetRetention = ($totalInvalid != 0) ? ($sales / $totalInvalid) : 0;
            $volumeGrossRetention = ($total != 0) ? ($sales / $total) : 0;

            $report->volume_net_retention = number_format($volumeNetRetention * 100, 2) . '%';
            $report->volume_gross_retention = number_format($volumeGrossRetention * 100, 2) . '%';
        }

        return $reportData;
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        if (!isset($filters->policyExpiryDate) && !isset($filters->month)) {
            $currentDate = Carbon::now();
            $previousMonthStartDate = $currentDate->copy()->subMonth()->startOfMonth();
            $nextMonthEndDate = $currentDate->copy()->addMonth()->endOfMonth();
            $previousMonthStartDateFormatted = $previousMonthStartDate->format($dateFormat);
            $nextMonthEndDateFormatted = $nextMonthEndDate->format($dateFormat);
            $query->whereBetween('policy_expiry_date', [$previousMonthStartDateFormatted, $nextMonthEndDateFormatted]);
            $this->applyFilterForBatch($query, $filters);
        }
    }

    public function applyFilterForBatch($query, $filter)
    {
        $query->selectRaw("quote_batches.name as batch,
        quote_batches.start_date,
        quote_batches.end_date")
        ->join('quote_batches', 'quote_batch_id', '=', 'quote_batches.id');

        if (isset($filter->policyExpiryDate)){
            $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
            $startDate = Carbon::parse($filter->policyExpiryDate[0])->startOfDay();
            $endDate = Carbon::parse($filter->policyExpiryDate[1])->endOfDay();
            $query->whereBetween('policy_expiry_date', [$startDate->format($dateFormat), $endDate->format($dateFormat)]);
        }
        $query->groupBy('batch');
    }

    public function applyFilterByMonth($query, $filters){
        $monthDates = $this->getMonthDatesByNumber(Carbon::now()->format('y'), $filters->month);
        $query->whereBetween('policy_expiry_date', [$monthDates['start_date'], $monthDates['end_date']]);
    }

    public function getUserPorductName()
    {
        $productName = '';
        $products = $this->getUserProducts(auth()->user()->id);
        if (count($products) != 0) {
            $productName = $products[0]->name;
        }
        return $productName;
    }

    function getMonthDatesByNumber($year, $monthNumber) {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        if ($monthNumber < 1 || $monthNumber > 12) {
            return ['error' => 'Invalid month number'];
        }
    
        $startDate = new DateTime("$year-$monthNumber-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
    
        return [
            'start_date' => $startDate->format($dateFormat),
            'end_date' => $endDate->format($dateFormat)
        ];
    }
    
}
