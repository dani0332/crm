<?php

namespace App\Factories;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\QuoteTypeId;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Strategies\CarAllocation;
use App\Strategies\HealthAllocation;

class ManagementReportServiceFactory
{
    public static function createStrategy($reportCategory)
    {
        $strategy = null;
        info('Inside strategy creation for report category : '.$reportCategory);
        if($reportCategory == ManagementReportCategoriesEnum::SALE_SUMMARY)
        {
            info('Sale Summary is about to trigger');
            $strategy = new SaleSummaryReportService();
        }

        return $strategy;
    }
}
