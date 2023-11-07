<?php

namespace App\Factories;

use App\Enums\ManagementReportCategoriesEnum;

class ManagementReportServiceFactory
{
    public static function createStrategy($reportCategory)
    {
        $strategy = null;
        info('Inside strategy creation for report category : '.$reportCategory);
        if ($reportCategory == ManagementReportCategoriesEnum::SALE_SUMMARY) {
            info('Sale Summary is about to trigger');
            $strategy = new SaleSummaryReportService();
        }

        return $strategy;
    }
}
