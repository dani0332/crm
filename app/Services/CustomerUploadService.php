<?php

namespace App\Services;

use App\Imports\CustomersImport;
use App\Jobs\RewardsBulkWEJob;
use App\Jobs\CreateQuoteCustomers;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Config;

class CustomerUploadService
{
    public function customerUploadRecordsCreate(Request $request)
    {
        if ($request->hasFile('file_name')
            && $request->has('cdb_id')) {
            $dateTimeFormat = Config::get('contacts.cu_datetime_format');
            
            $dateFrom = date($dateTimeFormat);

            Excel::import(new CustomersImport($request->myalfred_expiry_date), $request->file('file_name'));
            
            $dateTo = date($dateTimeFormat);

            // dispatch(new RewardsBulkWEJob(json_encode($request), $dateTo, $dateFrom));

            Log::channel('daily')->info('Executing Job for creating quote customers having data ---> '.$dateFrom. '| Date To '. $dateTo);
            dispatch(new CreateQuoteCustomers($dateFrom, $dateTo, $request->cdb_id));

        }

        return 1;
    }
}