<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
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
use Auth;

class TMLeadsImport implements ToModel
{
    private $rows = 0;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        ++$this->rows;

        $customerName = $row[0];
        $phoneNo = $row[1];
        $EmailId = strtolower(trim(ltrim(rtrim($row[2]))));
        $insuranceType = $row[3];
        $leadType = $row[4];
        $nationality = $row[5];
        $dob = date("Y-m-d", strtotime($row[6]));
        $yearsOfDriving = $row[7];
        $carManufacturer = $row[8];
        $model = $row[9];
        $yearOfManufacture = $row[10];
        $emiratesOfRegistration = $row[11];
        $carValue = $row[12];
        $notes = $row[13];
        $enquiryDate = date("Y-m-d", strtotime($row[14]));
        $createdDate = date("Y-m-d", strtotime($row[15]));
        $advisorEmail = $row[16];

        if($row[17] != "" && $row[18] != "") {
            $followpDate = date("Y-m-d", strtotime($row[17]));
            $followpTime = date("H:i:s", strtotime($row[18]));
            $followpDateTime = $followpDate." ".$followpTime;
            $followpDateTimeFinal = date("Y-m-d H:i:s", strtotime($followpDateTime));
        }
        else {
            $followpDateTimeFinal = NULL;
        }

        if($customerName != "Customer Name") {

            if (strpos($insuranceType, '-') !== false) { // Get Type of Insurance > Get string before hiphen '-'
                $tmInsuranceType = strstr($insuranceType, '-', true); // Motor Insurance TPL/Comp
            }
            else {
                $tmInsuranceType = $insuranceType; // Non Motor Insurance
            }

            $assignUserId = User::where('email', '=', $advisorEmail)->value('id');

            $tmLeadTypeId = TmLeadType::where('code', '=', $leadType)->value('id');
            $tmInsuranceTypeId = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('id');

            $tmLeadStatusCodeNewLead = tmLeadStatusCode::NewLead;
            $tmLeadStatusID = TmLeadStatus::where('code', '=', $tmLeadStatusCodeNewLead)->value('id');

            $tmInsuranceTypeCode = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('code');

            if($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {

                $carMakeId = CarMake::where('text', '=', $carManufacturer)->value('id');
                $carModelId = CarModel::where('text', '=', $model)->value('id');
                $nationalityId = Nationality::where('code', '=', $nationality)->value('id');
                $yearsOfDrivingId = UAELicenseHeldFor::where('code', '=', $yearsOfDriving)->value('id');
                $emiratesOfRegistrationId = Emirate::where('code', '=', $emiratesOfRegistration)->value('id');

                $yearOfManufacture = $yearOfManufacture;
                $carValue = $carValue;
                $carModelId = $carModelId;
                $carMakeId = $carMakeId;
                $nationalityId = $nationalityId;
                $yearsOfDrivingId = $yearsOfDrivingId;
                $emiratesOfRegistrationId = $emiratesOfRegistrationId;

                // Get Car Type of Insurance > Get string after hiphen '-'
                $tmCarInsuranceType = substr($insuranceType, strpos($insuranceType, "-") + 2);

                if($tmCarInsuranceType) {
                    if($tmCarInsuranceType == "TPL") {
                        $tmCarInsuranceTypeCode = carTypeInsuranceCode::ThirdPartyOnly;
                    }
                    if($tmCarInsuranceType == "Comp") {
                        $tmCarInsuranceTypeCode = carTypeInsuranceCode::Comprehensive;
                    }

                    $tmCarInsuranceTypeId = CarTypeInsurance::where('code', '=', $tmCarInsuranceTypeCode)->value('id');
                    $tmCarInsuranceTypeId = $tmCarInsuranceTypeId;
                }
            }

            if($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
                $yearOfManufacture = NULL;
                $carValue = NULL;
                $carModelId = NULL;
                $carMakeId = NULL;
                $nationalityId = NULL;
                $yearsOfDrivingId = NULL;
                $emiratesOfRegistrationId = NULL;
                $tmCarInsuranceTypeId = NULL;
            }

            if($tmInsuranceTypeCode == tmInsuranceTypeCode::Car || $tmInsuranceTypeCode == tmInsuranceTypeCode::Bike
            || $tmInsuranceTypeCode == tmInsuranceTypeCode::Life || $tmInsuranceTypeCode == tmInsuranceTypeCode::Health) {
                $dob = $dob;
            }
            else {
                $dob = NULL;
            }

            $newTmLead = new TmLead([
                "customer_name" => $customerName,
                "phone_number" => $phoneNo,
                "email_address" => $EmailId,
                "enquiry_date" => $enquiryDate,
                "allocation_date" => $createdDate,
                "notes" => $notes,
                "created_by_id" => Auth::user()->id,
                "assigned_to_id" => $assignUserId,
                "tm_lead_types_id" => $tmLeadTypeId,
                "tm_insurance_types_id" => $tmInsuranceTypeId,
                "tm_lead_statuses_id" => $tmLeadStatusID,
                "dob" => $dob,
                "year_of_manufacture" => $yearOfManufacture,
                "car_value" => $carValue,
                "car_model_id" => $carModelId,
                "car_make_id" => $carMakeId,
                "nationality_id" => $nationalityId,
                "years_of_driving_id" => $yearsOfDrivingId,
                "emirates_of_registration_id" => $emiratesOfRegistrationId,
                "car_type_insurance_id" => $tmCarInsuranceTypeId,
                "next_followup_date" => $followpDateTimeFinal,
                //"tm_upload_leads_id" => $SSSSS,
            ]);

            $newTmLead->save();

            $updateNewTmLead = TmLead::find($newTmLead->id);
            $updateNewTmLead->cdb_id = "TM-".$newTmLead->id;
            $updateNewTmLead->save();

            return $newTmLead;
        }

    }

    public function getRowCount(): int
    {
        return $this->rows;
    }
}
