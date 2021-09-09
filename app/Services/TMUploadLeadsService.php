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
ini_set('max_execution_time', 10000);

class TMUploadLeadsService
{
    public function tmUploadLeadsCreateUpdate(Request $request, $type, $tmUploadLeadID)
    {
        if ($request->hasFile('file_name')) {

            if($type == "create") {
                $tmUploadLead = new TmUploadLead();
            }
            if($type == "update") {
                $tmUploadLead = TmUploadLead::find($tmUploadLeadID);
            }

            $fileName = get_guid().'_'.$request->file_name->getClientOriginalName();
            $filePath = $request->file('file_name')->storeAs('/', $fileName, 'azure');
            $tmUploadLead->file_name = $request->file_name->getClientOriginalName();
            $tmUploadLead->file_path = $filePath;

            $csvTmUploadLeads = new \ParseCsv\Csv();

            $csvTmUploadLeads->auto($request->file('file_name'));
            $empty_field = "";
            foreach($csvTmUploadLeads->data as $x => $val) {
                if($val["Customer Name"] == "") { $empty_field .= "Customer Name"; }
                if($val["Phone No"] == "") { $empty_field .= "Phone No"; }
                if($val["Email Id"] == "") { $empty_field .= "Email Id"; }
                if($val["Insurance Type"] == "") { $empty_field .= "Insurance Type"; }
                if($val["Enquiry Date"] == "") { $empty_field .= "Enquiry Date"; }
                if($val["Created Date"] == "") { $empty_field .= "Created Date"; }

                // Get Type of Insurance > Get string before hiphen '-'
                if (strpos($val['Insurance Type'], '-') !== false) {
                    $tmInsuranceType = strstr($val['Insurance Type'], '-', true); // Motor Insurance TPL/Comp
                }
                else {
                    $tmInsuranceType = $val['Insurance Type']; // Non Motor Insurance
                }

                $assignedToID = User::where('email', '=', $val["Advisor email"])->value('id');
                $tmLeadTypeID = TmLeadType::where('code', '=', $val["Lead Type"])->value('id');
                $tmInsuranceTypeID = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('id');
                $tmInsuranceTypeCode = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('code');
                $tmLeadStatusCodeNewLead = tmLeadStatusCode::NewLead;
                $tmLeadStatusID = TmLeadStatus::where('code', '=', $tmLeadStatusCodeNewLead)->value('id');

                $newTmLead = new TmLead();
                $newTmLead->customer_name = $val["Customer Name"];
                $newTmLead->phone_number = $val["Phone No"];
                $newTmLead->email_address = strtolower(trim(ltrim(rtrim($val["Email Id"]))));
                $newTmLead->enquiry_date = $val["Enquiry Date"];
                $newTmLead->allocation_date = $val["Created Date"];
                $newTmLead->notes = $val["Notes"];
                $newTmLead->created_by_id = Auth::user()->id;
                $newTmLead->assigned_to_id = $assignedToID;
                $newTmLead->tm_lead_types_id = $tmLeadTypeID;
                $newTmLead->tm_insurance_types_id = $tmInsuranceTypeID;
                $newTmLead->tm_lead_statuses_id = $tmLeadStatusID;

                if($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {

                    $carMakeID = CarMake::where('text', '=', $val["Car Manufacturer"])->value('id');
                    $carModelID = CarModel::where('text', '=', $val["Model"])->value('id');
                    $nationalityID = Nationality::where('code', '=', $val["Nationality"])->value('id');
                    $yearsOfDrivingID = UAELicenseHeldFor::where('code', '=', $val["Years of driving"])->value('id');
                    $emiratesOfRegistrationID = Emirate::where('code', '=', $val["Emirates of registration"])->value('id');

                    $newTmLead->dob = $val["DOB"];
                    $newTmLead->year_of_manufacture = $val["Year of manufacture"];
                    $newTmLead->car_value = $val["Car Value"];
                    $newTmLead->car_model_id = $carModelID;
                    $newTmLead->car_make_id = $carMakeID;
                    $newTmLead->nationality_id = $nationalityID;
                    $newTmLead->years_of_driving_id = $yearsOfDrivingID;
                    $newTmLead->emirates_of_registration_id = $emiratesOfRegistrationID;

                    // Get Car Type of Insurance > Get string after hiphen '-'
                    $tmCarInsuranceType = substr($val['Insurance Type'], strpos($val['Insurance Type'], "-") + 2);

                    if($tmCarInsuranceType) {
                        if($tmCarInsuranceType == "TPL") {
                            $tmCarInsuranceTypeCode = carTypeInsuranceCode::ThirdPartyOnly;
                        }
                        if($tmCarInsuranceType == "Comp") {
                            $tmCarInsuranceTypeCode = carTypeInsuranceCode::Comprehensive;
                        }

                        $tmCarInsuranceTypeID = CarTypeInsurance::where('code', '=', $tmCarInsuranceTypeCode)->value('id');
                        $newTmLead->car_type_insurance_id = $tmCarInsuranceTypeID;
                    }
                }
                if($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
                    $newTmLead->dob = NULL;
                    $newTmLead->year_of_manufacture = NULL;
                    $newTmLead->car_value = NULL;
                    $newTmLead->car_model_id = NULL;
                    $newTmLead->car_make_id = NULL;
                    $newTmLead->nationality_id = NULL;
                    $newTmLead->years_of_driving_id = NULL;
                    $newTmLead->emirates_of_registration_id = NULL;
                    $newTmLead->car_type_insurance_id = NULL;
                }

                $newTmLead->tm_upload_leads_id = $tmUploadLead->id;
                $newTmLead->save();

                $updateNewTmLead = TmLead::find($newTmLead->id);
                $updateNewTmLead->cdb_id = "TM-".$newTmLead->id;
                $updateNewTmLead->save();

                // Link record with TM Lead
            }

            $csvTmUploadLeads->loadFile($request->file('file_name'));
            $countRows = $csvTmUploadLeads->getTotalDataRowCount();
            $tmUploadLead->total_records = $countRows;
            $tmUploadLead->good = $countRows;
            $tmUploadLead->cannot_upload = "0";

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
}