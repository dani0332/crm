<?php

namespace App\Imports;

use App\Enums\carTypeInsuranceCode;
use App\Enums\tmInsuranceTypeCode;
use App\Enums\tmLeadStatusCode;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarTypeInsurance;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\TmInsuranceType;
use App\Models\TmLead;
use App\Models\TmLeadStatus;
use App\Models\TmLeadType;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TMLeadsImport implements SkipsOnFailure, ToModel, WithChunkReading, WithStartRow, WithValidation
{
    use Importable, SkipsFailures;

    private $rows = 0;

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->rows++;

        $customerName = $row[0];
        $phoneNo = $row[1];
        $emailId = strtolower(trim($row[2]));
        $insuranceType = $row[3];
        $leadType = $row[4];
        $nationality = $row[5];
        $dob = strtr($row[6], '/', '-');
        $dobFinal = date('Y-m-d', strtotime($dob));
        $yearsOfDriving = $row[7];
        $carManufacturer = $row[8];
        $model = $row[9];
        $yearOfManufacture = $row[10];
        $emiratesOfRegistration = $row[11];
        $carValue = $row[12];
        $notes = $row[13];
        $enquiryDate = strtr($row[14], '/', '-');
        $enquiryDateFinal = date('Y-m-d', strtotime($enquiryDate));
        $createdDate = strtr($row[15], '/', '-');
        $createdDateFinal = date('Y-m-d', strtotime($createdDate));
        $advisorEmail = $this->changeEmailDomain($row[16]);

        if ($row[17] != '' && $row[18] != '') {
            $followpDate = strtr($row[17], '/', '-');
            $followpDatebFinal = date('Y-m-d', strtotime($followpDate));
            $followpTime = date('H:i:s', strtotime($row[18]));
            $followpDateTime = $followpDatebFinal.' '.$followpTime;
            $followpDateTimeFinal = date('Y-m-d H:i:s', strtotime($followpDateTime));
        } else {
            $followpDateTimeFinal = null;
        }

        if (strpos($insuranceType, '-') !== false) { // Get Type of Insurance > Get string before hiphen '-'
            $tmInsuranceType = strstr($insuranceType, '-', true); // Motor Insurance TPL/Comp
        } else {
            $tmInsuranceType = $insuranceType; // Non Motor Insurance
        }

        $assignUserId = User::where('email', '=', $advisorEmail)->value('id');

        $tmLeadTypeId = TmLeadType::where('code', '=', $leadType)->value('id');
        $tmInsuranceTypeId = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('id');

        $tmLeadStatusCodeNewLead = tmLeadStatusCode::NewLead;
        $tmLeadStatusID = TmLeadStatus::where('code', '=', $tmLeadStatusCodeNewLead)->value('id');

        $tmInsuranceTypeCode = TmInsuranceType::where('text', '=', $tmInsuranceType)->value('code');

        if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {
            $carMakeId = CarMake::where('text', '=', $carManufacturer)->value('id');
            $carModelId = CarModel::where('text', '=', $model)->value('id');
            $nationalityId = Nationality::where('code', '=', $nationality)->value('id');
            $yearsOfDrivingId = UAELicenseHeldFor::where('code', '=', $yearsOfDriving)->value('id');
            $emiratesOfRegistrationId = Emirate::where('code', '=', $emiratesOfRegistration)->value('id');

            // Get Car Type of Insurance > Get string after hiphen '-'
            $tmCarInsuranceType = substr($insuranceType, strpos($insuranceType, '-') + 2);

            if ($tmCarInsuranceType) {
                if ($tmCarInsuranceType == 'TPL') {
                    $tmCarInsuranceTypeCode = carTypeInsuranceCode::ThirdPartyOnly;
                }
                if ($tmCarInsuranceType == 'Comp') {
                    $tmCarInsuranceTypeCode = carTypeInsuranceCode::Comprehensive;
                }

                $tmCarInsuranceTypeId = CarTypeInsurance::where('code', '=', $tmCarInsuranceTypeCode)->value('id');
            }
        }

        if ($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
            $yearOfManufacture = null;
            $carValue = null;
            $carModelId = null;
            $carMakeId = null;
            $nationalityId = null;
            $yearsOfDrivingId = null;
            $emiratesOfRegistrationId = null;
            $tmCarInsuranceTypeId = null;
        }

        if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car || $tmInsuranceTypeCode == tmInsuranceTypeCode::Bike
        || $tmInsuranceTypeCode == tmInsuranceTypeCode::Life || $tmInsuranceTypeCode == tmInsuranceTypeCode::Health) {
            $dob = $dobFinal;
        } else {
            $dob = null;
        }

        $newTmLead = new TmLead([
            'customer_name' => $customerName,
            'phone_number' => $phoneNo,
            'email_address' => $emailId,
            'enquiry_date' => $enquiryDateFinal,
            'allocation_date' => $createdDateFinal,
            'notes' => $notes,
            'created_by_id' => Auth::user()->id,
            'assigned_to_id' => $assignUserId,
            'tm_lead_types_id' => $tmLeadTypeId,
            'tm_insurance_types_id' => $tmInsuranceTypeId,
            'tm_lead_statuses_id' => $tmLeadStatusID,
            'dob' => $dob,
            'year_of_manufacture' => $yearOfManufacture,
            'car_value' => $carValue,
            'car_model_id' => $carModelId,
            'car_make_id' => $carMakeId,
            'nationality_id' => $nationalityId,
            'years_of_driving_id' => $yearsOfDrivingId,
            'emirates_of_registration_id' => $emiratesOfRegistrationId,
            'car_type_insurance_id' => $tmCarInsuranceTypeId,
            'next_followup_date' => $followpDateTimeFinal,
        ]);

        $newTmLead->save();

        $updateNewTmLead = TmLead::find($newTmLead->id);
        $updateNewTmLead->cdb_id = 'TM-'.$newTmLead->id;
        $updateNewTmLead->save();

        return $newTmLead;
    }

    // if email has afia.ae domain replace with insurancemarket.ae domain
    public function changeEmailDomain($email)
    {
        $emailTrimmed = strtolower(trim($email));
        if (strpos($emailTrimmed, '@afia.ae')) {
            $advisorEmail = str_replace('@afia.ae', '@insurancemarket.ae', $emailTrimmed);
        } else {
            $advisorEmail = $emailTrimmed;
        }

        return $advisorEmail;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [
            '*.0' => [ // Customer Name
                'required',
                'max:50',
            ],
            '*.1' => [ // Phone No
                'required',
                'max:12',
            ],
            '*.2' => [ // Email Id
                'required',
                'max:50',
            ],
            '*.4' => [ // Lead Type
                'required',
            ],
            '*.14' => [ // Enquiry Date
                'required',
                'date_format:d/m/Y',
            ],
            '*.15' => [ // Created/Allocation Date
                'required',
                'date_format:d/m/Y',
            ],
            '*.12' => [ // Car Value
                'nullable',
                'numeric',
                'between:0,9999999999.99',
            ],
            '*.6' => [ // DOB
                'nullable',
                'date_format:d/m/Y',
            ],
            '*.17' => [ // Followup Date
                'nullable',
                'date_format:d/m/Y',
            ],
            '*.13' => [ // Notes
                'nullable',
                'max:500',
            ],
            '*.16' => [ // Advisor email
                'nullable',
                'max:60',
            ],
        ];
    }
}
