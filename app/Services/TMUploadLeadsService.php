<?php

namespace App\Services;
use App\Models\TmUploadLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Config;
use Auth;
use App\Enums\tmInsuranceTypeCode;
use App\Enums\carTypeInsuranceCode;
use App\Enums\tmLeadStatusCode;
use App\Models\TmLead;
use App\Models\User;
use App\Models\TmLeadType;
use App\Models\TmInsuranceType;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Nationality;
use App\Models\UAELicenseHeldFor;
use App\Models\Emirate;
use App\Models\CarTypeInsurance;
use App\Models\TmLeadStatus;
use App\Imports\TMLeadsImport;
use Maatwebsite\Excel\Facades\Excel;
ini_set('max_execution_time', 10000);

class TMUploadLeadsService
{
    public function tmUploadLeadsCreateUpdate(Request $request, $type, $tmUploadLeadID)
    {
        if ($request->hasFile('file_name')) {

            $tmLeadsImport = new TMLeadsImport;
            Excel::import($tmLeadsImport, $request->file('file_name')->getLinkTarget());
            $countRows = $tmLeadsImport->getRowCount() - 1;

            $tmUploadLead = new TmUploadLead();
            $tmUploadLead->total_records = $countRows;
            $tmUploadLead->good = $countRows;
            $tmUploadLead->cannot_upload = "0";
            $tmUploadLead->created_by_id = Auth::user()->id;
            $tmUploadLead->save();

        }
    }
}