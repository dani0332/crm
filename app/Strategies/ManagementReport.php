<?php

namespace App\Strategies;

use Illuminate\Http\Request;

interface ManagementReport
{
    public function getReportData(Request $request);
    public function getFilterOptions();
    public function getDefaultFilters();
    public function applyFilters($query, $filters);
}
