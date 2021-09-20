<?php

namespace App\Services;

use App\Imports\CustomersImport;
use App\Models\BusinessQuote;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class CustomerUploadService
{
    public function customerUploadRecordsCreate(Request $request)
    {
        $businessQuote = BusinessQuote::where('code', '=', $request->cdb_id)->get();
        if(!$businessQuote->isEmpty()){
            if ($request->hasFile('file_name') && $request->has('cdb_id')) {
                Excel::import(new CustomersImport($request->myalfred_expiry_date, $request->cdb_id), $request->file('file_name'));
            }
            return 1;
        }else {
            return 0;
        }
    }
}
