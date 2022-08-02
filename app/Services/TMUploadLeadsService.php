<?php

namespace App\Services;

use App\Imports\TMLeadsImport;
use App\Models\TmUploadLead;
use Auth;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

ini_set('max_execution_time', 10000);

class TMUploadLeadsService
{
    public function tmUploadLeadsCreateUpdate(Request $request, $type, $tmUploadLeadID)
    {
        // if ($request->hasFile('file_name')) {

        //     $tmLeadsImport = new TMLeadsImport;
        //     $fileNameOriginal = $request->file_name->getClientOriginalName();
        //     $fileNameAzure = get_guid().'_'.$fileNameOriginal;
        //     $filePathAzure = $request->file('file_name')->storeAs('/', $fileNameAzure, 'azure');

        //     $tmLeadsImport->import(request()->file('file_name'));

        //     foreach ($tmLeadsImport->failures() as $failure) {
        //          $failure->row(); // row that went wrong
        //          $failure->attribute(); // either heading key (if using heading row concern) or column index
        //          $failure->errors(); // Actual error messages from Laravel validator
        //          $failure->values(); // The values of the row that has failed.

        //     }

        //         //Excel::import($tmLeadsImport,request()->file('file_name'));
        //     $countRows = $tmLeadsImport->getRowCount();

        //     $tmUploadLead = new TmUploadLead();
        //     $tmUploadLead->file_name = $fileNameOriginal;
        //     $tmUploadLead->file_path = $filePathAzure;
        //     $tmUploadLead->total_records = $countRows;
        //     $tmUploadLead->good = $countRows;
        //     $tmUploadLead->cannot_upload = "0";
        //     $tmUploadLead->created_by_id = Auth::user()->id;
        //     $tmUploadLead->save();

        // }
    }
}
