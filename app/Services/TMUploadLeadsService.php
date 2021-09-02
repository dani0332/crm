<?php

namespace App\Services;
use App\Models\TmUploadLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Config;
use Illuminate\Http\Request;
use Auth;

class TMUploadLeadsService
{
    public function tmUploadLeadsCreateUpdate(Request $request, $type, $tmUploadLeadID)
    {
        if($type == "create") {
            $tmUploadLead = new TmUploadLead();
        }
        if($type == "update") {
            $tmUploadLead = TmUploadLead::find($tmUploadLeadID);
        }

        if ($request->hasFile('file_name')) {
            $fileName = get_guid().'_'.$request->file_name->getClientOriginalName();
            $filePath = $request->file('file_name')->storeAs('/', $fileName, 'azure');
            $tmUploadLead->file_name = $request->file_name->getClientOriginalName();
            $tmUploadLead->file_path = $filePath;

            $csvTmUploadLeads = new \ParseCsv\Csv();
            $csvTmUploadLeads->loadFile($request->file('file_name'));
            $countRows = $csvTmUploadLeads->getTotalDataRowCount();
            $tmUploadLead->total_records = $countRows;
            $tmUploadLead->good = $countRows;
            $tmUploadLead->cannot_upload = "0";

            $csvTmUploadLeads->auto($request->file('file_name'));
            $empty_field = "";
            foreach($csvTmUploadLeads->data as $x => $val) {
                if($val["Customer Name"] == "") { $empty_field .= "Customer Name"; }
                if($val["Phone No"] == "") { $empty_field .= "Phone No"; }
                if($val["Email Id"] == "") { $empty_field .= "Email Id"; }
                if($val["Insurance Type"] == "") { $empty_field .= "Insurance Type"; }
                if($val["Enquiry Date"] == "") { $empty_field .= "Enquiry Date"; }
                if($val["Created Date"] == "") { $empty_field .= "Created Date"; }
                

            }

        }
        if($type == "create") {
            $tmUploadLead->created_by_id = Auth::user()->id;
        }
        if($type == "update") {
            $tmUploadLead->modified_by_id = Auth::user()->id;
        }
        $tmUploadLead->save();

        return $tmUploadLead->id;
    }
}