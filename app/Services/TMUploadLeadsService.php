<?php

namespace App\Services;
use App\Models\TmUploadLead;
use Illuminate\Http\Request;
use Auth;
use App\Imports\TMLeadsImport;
use Maatwebsite\Excel\Facades\Excel;
ini_set('max_execution_time', 10000);

class TMUploadLeadsService
{
    public function tmUploadLeadsCreateUpdate(Request $request, $type, $tmUploadLeadID)
    {
        if ($request->hasFile('file_name')) {

            //dd($request->file_name);

            $tmLeadsImport = new TMLeadsImport;
            $fileNameOriginal = $request->file_name->getClientOriginalName();
            $fileNameAzure = get_guid().'_'.$fileNameOriginal;
            $filePathAzure = $request->file('file_name')->storeAs('/', $fileNameAzure, 'azure');
            Excel::import($tmLeadsImport,request()->file('file_name'));
            //Excel::import($tmLeadsImport, $request->file('file_name')->getRealPath());
            $countRows = $tmLeadsImport->getRowCount() - 1;

            $tmUploadLead = new TmUploadLead();
            $tmUploadLead->file_name = $fileNameOriginal;
            $tmUploadLead->file_path = $filePathAzure;
            $tmUploadLead->total_records = $countRows;
            $tmUploadLead->good = $countRows;
            $tmUploadLead->cannot_upload = "0";
            $tmUploadLead->created_by_id = Auth::user()->id;
            $tmUploadLead->save();

        }
    }
}
