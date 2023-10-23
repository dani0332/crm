<?php

namespace App\Services;

use App\Imports\CustomersImport;
use App\Models\BusinessQuote;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerUploadService
{
    public $sendEmailCustomerService;
    public $berlinService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->berlinService = $berlinService;
    }

    public function customerUploadRecordsCreate(Request $request)
    {
        $businessQuote = BusinessQuote::where('code', '=', $request->cdb_id)->get();
        if (! $businessQuote->isEmpty()) {
            if ($request->hasFile('file_name') && $request->has('cdb_id')) {
                Excel::import(new CustomersImport($request->myalfred_expiry_date, $request->cdb_id, $request->inviatation_email, $this->sendEmailCustomerService, $this->berlinService), $request->file('file_name'));
            }

            return 1;
        } else {
            return 0;
        }
    }
}
