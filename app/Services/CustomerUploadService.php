<?php

namespace App\Services;

use App\Imports\CustomersImport;
use DateTime;
use App\Http\Controllers\BulkEmailProcessController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class CustomerUploadService
{
    public function customerUploadRecordsCreate(Request $request)
    {
        if ($request->hasFile('file_name')) {
            
            $dateFrom = new DateTime();
            $dateFrom = $dateFrom->format('Y-m-d H:i:s');
            
            Excel::import(new CustomersImport, $request->file('file_name')->getLinkTarget());
            
            $dateTo = new DateTime();
            $dateTo = $dateTo->format('Y-m-d H:i:s');

            $request->dateFrom = $dateFrom;
            $request->dateTo = $dateTo;

            $sendBulkEmail = new BulkEmailProcessController();
            $sendBulkEmail->ProcessBulkWelcomeEmails($request);
        }

        return 1;
    }
}