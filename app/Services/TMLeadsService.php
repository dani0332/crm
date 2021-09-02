<?php

namespace App\Services;
use App\Models\TmLead;
use App\Models\TmInsuranceType;
use App\Models\TmCallStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Config;
use Illuminate\Http\Request;
use App\Enums\tmInsuranceTypeCode;
use Auth;

class TMLeadsService
{
    public function tmLeadsCreateUpdate(Request $request, $type, $tmLeadID)
    {
        $tmInsuranceTypeCode = TmInsuranceType::where('id', '=', $request->tm_insurance_types_id)->value('code');

        if($type == "create") {
            $tmLead = new TmLead();
        }
        if($type == "update") {
            $tmLead = TmLead::find($tmLeadID);
        }

        $tmLead->customer_name = $request->customer_name;
        $tmLead->phone_number = $request->phone_number;
        $tmLead->email_address = strtolower(trim(ltrim(rtrim($request->email_address))));
        $tmLead->enquiry_date = $request->enquiry_date;
        $tmLead->allocation_date = $request->allocation_date;
        $tmLead->notes = $request->notes;
        $tmLead->next_followup_date = $request->next_followup_date;
        $tmLead->assigned_to_id = $request->assigned_to_id;
        if($type == "create") {
            $tmLead->created_by_id = Auth::user()->id;
        }
        if($type == "update") {
            $tmLead->modified_by_id = Auth::user()->id;
        }
        $tmLead->tm_lead_types_id = $request->tm_lead_types_id;
        $tmLead->tm_call_statuses_id = $request->tm_call_statuses_id;
        $tmLead->tm_lead_statuses_id = $request->tm_lead_statuses_id;
        $tmLead->tm_insurance_types_id = $request->tm_insurance_types_id;

        if($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {
            $tmLead->dob = $request->dob;
            $tmLead->year_of_manufacture = $request->year_of_manufacture;
            $tmLead->car_value = $request->car_value;
            $tmLead->car_model_id = $request->car_model_id;
            $tmLead->car_make_id = $request->car_make_id;
            $tmLead->nationality_id = $request->nationality_id;
            $tmLead->years_of_driving_id = $request->years_of_driving_id;
            $tmLead->emirates_of_registration_id = $request->emirates_of_registration_id;
            $tmLead->car_type_insurance_id =$request->car_type_insurance_id;
        }

        if($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
            $tmLead->dob = NULL;
            $tmLead->year_of_manufacture = NULL;
            $tmLead->car_value = NULL;
            $tmLead->car_model_id = NULL;
            $tmLead->car_make_id = NULL;
            $tmLead->nationality_id = NULL;
            $tmLead->years_of_driving_id = NULL;
            $tmLead->emirates_of_registration_id = NULL;
            $tmLead->car_type_insurance_id = NULL;
        }

        $tmLead->save();

        $updateTmLead = TmLead::find($tmLead->id);
        $updateTmLead->cdb_id = "TM-".$tmLead->id;
        $updateTmLead->save();

        return $tmLead->id;
    }
}