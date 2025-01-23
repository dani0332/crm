<?php

namespace App\Imports;

use App\Enums\CurrentInsurersEnums;
use App\Jobs\RenewalImportJob;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\RenewalsUploadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class RenewalsImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithStartRow, WithValidation
{
    use Importable, SkipsFailures;

    private $rows = 0;
    private $renewalsUploadService;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;

    public function __construct(RenewalsUploadService $renewalsUploadService, $fileName, $renewalImportCode, $uploadType)
    {
        $this->renewalsUploadService = $renewalsUploadService;
        $this->fileName = $fileName;
        $this->renewalImportCode = $renewalImportCode;
        $this->uploadType = $uploadType;
    }

    public function onRow(Row $row)
    {
        $this->rows++;
        $row = $row->toArray();
        $qouteType = $row[2];
        $email = $row[1];

        if (! empty($email)) {
            $quoteData = 0;

            // customer information
            $customerName = explode(' ', $row[0], 2);
            $lastName = '';
            if (! empty($customerName[1])) {
                $firstName = $customerName[0];
                $lastName = $customerName[1];
            } else {
                $firstName = $row[0];
                $lastName = '';
            }

            // product information
            $insurer = $this->insurersMapping($row[3]);
            $product = $row[4];
            $productType = $row[5];
            $source = $row[6];

            // car quote information
            $carMake = $row[17];
            $carModel = $row[18];
            $carYear = $row[19];

            // other information
            $customerPhone = $row[7];
            $advisor = preg_replace('/\s/', '', strtolower(trim($row[8])));
            $previousAdvisor = preg_replace('/\s/', '', strtolower(trim($row[9])));
            $policy = $row[10];
            $batch = $row[11];
            $startDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[12]))->toDateTimeString();
            $endDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[13]))->toDateTimeString();
            $object = $row[14];
            $premium = $row[15];
            $notes = $row[16];

            $emailResult = $this->sanitizeEmail($email);
            $phoneResult = $this->sanitizePhoneNumber($customerPhone);

            $email = $emailResult['0']; // assign the sanitized email to the email variable
            $customerPhone = $phoneResult['0']; // assign the sanitized phone number to the phone variable
            $notes = $emailResult['1'] != '' ? $notes.$emailResult['2'] : $notes; // if the notes variable is not empty, add the notes from the email sanitization to the notes variable
            $notes = $phoneResult['1'] != '' ? $notes.$phoneResult['2'] : $notes; // if the notes variable is not empty, add the notes from the phone sanitization to the notes variable

            $findCustomerByEmail = CustomerService::getCustomerByEmail($email);
            if (! $findCustomerByEmail) {
                $newCustomer = new Customer([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'mobile_no' => $customerPhone,
                    'has_alfred_access' => false,
                    'has_reward_access' => false,
                ]);
                $newCustomer->save();
                $customerId = $newCustomer->getAttribute('id');
                $quoteData = (object) [
                    'customer_id' => $customerId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $customerPhone,
                    'insurer' => $insurer,
                    'product' => $product,
                    'product_type' => $productType,
                    'source' => $source,
                    'make' => $carMake,
                    'model' => $carModel,
                    'year' => $carYear,
                    'advisor' => $advisor,
                    'pAdvisor' => $previousAdvisor,
                    'policy' => $policy,
                    'batch' => $batch,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'object' => $object,
                    'gross_premium' => $premium,
                    'notes' => $notes,
                    'other_email_ids' => $emailResult['1'],
                ];
            } else {
                $customer = $findCustomerByEmail;
                $quoteData = (object) [
                    'customer_id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phoneNumber' => $customerPhone,
                    'insurer' => $insurer,
                    'product' => $product,
                    'product_type' => $productType,
                    'source' => $source,
                    'make' => $carMake,
                    'model' => $carModel,
                    'year' => $carYear,
                    'advisor' => $advisor,
                    'pAdvisor' => $previousAdvisor,
                    'policy' => $policy,
                    'batch' => $batch,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'object' => $object,
                    'gross_premium' => $premium,
                    'notes' => $notes,
                    'other_email_ids' => $emailResult['1'],
                ];
            }

            dispatch(new RenewalImportJob($quoteData, $qouteType, $this->renewalsUploadService, $this->fileName, $this->renewalImportCode, $this->uploadType, Auth::user()->id));
        }
    }

    public function sanitizePhoneNumber($phone)
    {
        $delimiterArray = [',', ':', '/', ';']; // delimiters
        $phoneClean = preg_replace('/\s/', '', strtolower(trim($phone)));
        $phoneNoReplaceComma = str_replace($delimiterArray, ',', $phoneClean);
        $primaryPhoneNumber = strtok($phoneNoReplaceComma, ',');

        if (strpos($phoneNoReplaceComma, ',') !== false) {
            $otherphoneNos = substr($phoneNoReplaceComma, strpos($phoneNoReplaceComma, ',') + 1);
        } else {
            $otherphoneNos = '';
        }

        $notes = ' - Additional phone numbers from phone column : '.$otherphoneNos;

        return [$primaryPhoneNumber, $otherphoneNos, $notes];
    }

    public function sanitizeEmail($email)
    {
        $delimiterArray = [',', ':', '/', ';'];	// delimiters
        $emailClean = preg_replace('/\s/', '', strtolower(trim($email)));
        $emailReplaceComma = str_replace($delimiterArray, ',', $emailClean);
        $primaryEmail = strtok($emailReplaceComma, ',');

        if (strpos($emailReplaceComma, ',') !== false) {
            $otherEmailIds = substr($emailReplaceComma, strpos($emailReplaceComma, ',') + 1);
        } else {
            $otherEmailIds = '';
        }

        $notes = ' - Additional Emails from email column : '.$otherEmailIds;

        return [$primaryEmail, $otherEmailIds, $notes];
    }

    public function insurersMapping($insurerName)
    {
        $insurerName = strtolower(trim($insurerName));
        switch ($insurerName) {
            case strpos($insurerName, CurrentInsurersEnums::TokioMarine) !== false:
                $insurerinCdb = 'Tokio Marine & Nichido Fire Insurance Co';
                break;
            case strpos($insurerName, CurrentInsurersEnums::NewIndia) !== false:
                $insurerinCdb = 'New India Assurance';
                break;
            case strpos($insurerName, CurrentInsurersEnums::Axa) !== false || strpos($insurerName, CurrentInsurersEnums::Gig) !== false:
                $insurerinCdb = 'GIG Gulf (AXA)';
                break;
            case strpos($insurerName, CurrentInsurersEnums::AbudhabiNational) !== false:
                $insurerinCdb = 'Abu Dhabi National Insurance Company';
                break;
            case (strpos($insurerName, CurrentInsurersEnums::Royal) !== false && strpos($insurerName, CurrentInsurersEnums::Sun)) || strpos($insurerName, CurrentInsurersEnums::Rsa) !== false:
                $insurerinCdb = 'Royal & Sun Alliance Insurance (RSA)';
                break;
            case strpos($insurerName, CurrentInsurersEnums::QatarInsurance) !== false || strpos($insurerName, CurrentInsurersEnums::Qic) !== false:
                $insurerinCdb = 'Qatar Insurance Company';
                break;
            case strpos($insurerName, CurrentInsurersEnums::NationalGeneralInsurance) !== false || strpos($insurerName, CurrentInsurersEnums::Ngi) !== false:
                $insurerinCdb = 'National General Insurance';
                break;
            case strpos($insurerName, CurrentInsurersEnums::Salama) !== false:
                $insurerinCdb = 'Salama Insurance';
                break;
            case strpos($insurerName, CurrentInsurersEnums::NoorTakaful) !== false:
                $insurerinCdb = 'Noor Takaful';
                break;
            case strpos($insurerName, CurrentInsurersEnums::OrientalInsurance) !== false:
                $insurerinCdb = 'Oriental Insurance';
                break;
            case strpos($insurerName, CurrentInsurersEnums::UnionInsurance) !== false:
                $insurerinCdb = 'Union Insurance';
                break;
            case strpos($insurerName, CurrentInsurersEnums::OmanInsurance) !== false || strpos($insurerName, CurrentInsurersEnums::Oic) !== false:
                $insurerinCdb = 'Oman Insurance Company';
                break;
            case strpos($insurerName, CurrentInsurersEnums::AbudhabiNationalTakaful) !== false:
                $insurerinCdb = 'Abu Dhabi National Takaful';
                break;
            case strpos($insurerName, CurrentInsurersEnums::Watania) !== false:
                $insurerinCdb = 'Watania';
                break;
            case strpos($insurerName, CurrentInsurersEnums::InsuranceHouse) !== false:
                $insurerinCdb = 'Insurance House';
                break;
            case strpos($insurerName, CurrentInsurersEnums::Other) !== false:
                $insurerinCdb = 'Other';
                break;
            default:
                $insurerinCdb = $insurerName;
                break;
        }

        return $insurerinCdb;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [
            '*.0' => function ($attribute, $value, $onFailure) { // Customer Name
                if (! $value) {
                    $onFailure('Customer Name is required');
                }
                if (strlen($value) > 100) {
                    $onFailure('Customer Name should not exceed length of 100 characters');
                }
            },
            '*.1' => function ($attribute, $value, $onFailure) { // Customer Email
                if (! $value) {
                    $onFailure('Customer Email is required');
                }
                if (strlen($value) > 255) {
                    $onFailure('Customer Email should not exceed length of 255 characters');
                }
            },
            '*.2' => function ($attribute, $value, $onFailure) { // Type
                if (! $value) {
                    $onFailure('Type of quote is required');
                }
                if (strlen($value) > 4) {
                    $onFailure('Type of quote should not exceed length of 4 characters');
                }
            },
            '*.3' => function ($attribute, $value, $onFailure) { // Insurer
                if (! $value) {
                    $onFailure('Insurer is required');
                }
                if (strlen($value) > 100) {
                    $onFailure('Insurer should not exceed length of 100 characters');
                }
            },
            '*.4' => function ($attribute, $value, $onFailure) { // Product
                if (! $value) {
                    $onFailure('Product is required');
                }
                if (strlen($value) > 100) {
                    $onFailure('Product should not exceed length of 100 characters');
                }
            },
            '*.5' => function ($attribute, $value, $onFailure) { // Product Type
                if (strlen($value) > 100) {
                    $onFailure('Product Type should not exceed length of 100 characters');
                }
                if (strlen($value) > 0 && $value != 'Comprehensive' && $value != 'Third Party Only') {
                    // $onFailure('Product Type should be either Comprehensive or Third Party  Only');
                }
            },
            '*.6' => function ($attribute, $value, $onFailure) { // Sales Channel
                if (strlen($value) > 100) {
                    $onFailure('Sales Channel should not exceed length of 100 characters');
                }
            },
            '*.7' => function ($attribute, $value, $onFailure) { // Customer Mobile
                if (strlen($value) > 100) {
                    $onFailure('Customer Mobile should not exceed length of 100 characters');
                }
            },
            '*.8' => function ($attribute, $value, $onFailure) { // Advisor Email
                if (strlen($value) > 100) {
                    $onFailure('Advisor Email should not exceed length of 100 characters');
                }
            },
            '*.9' => function ($attribute, $value, $onFailure) { // Previous Advisor Email
                if (strlen($value) > 100) {
                    $onFailure('Previous Advisor Email should not exceed length of 100 characters');
                }
            },
            '*.10' => function ($attribute, $value, $onFailure) { // Policy
                if (! $value) {
                    $onFailure('Policy is required');
                }
                if (strlen($value) > 100) {
                    $onFailure('Policy should not exceed length of 100 characters');
                }
            },
            '*.11' => function ($attribute, $value, $onFailure) { // Batch
                if (strlen($value) > 25) {
                    $onFailure('Batch should not exceed length of 25 characters');
                }
            },
            '*.12' => function ($attribute, $value, $onFailure) { // Start Date
                if (strlen($value) > 10) {
                    $onFailure('Start Date should not exceed length of 10 characters');
                }
            },
            '*.13' => function ($attribute, $value, $onFailure) { // End Date
                if (! $value) {
                    $onFailure('End Date is required');
                }
                if (strlen($value) > 10) {
                    $onFailure('End Date should not exceed length of 10 characters');
                }
            },
            '*.14' => function ($attribute, $value, $onFailure) { // Object
                if (strlen($value) > 200) {
                    $onFailure('Object should not exceed length of 200 characters');
                }
            },
            '*.15' => function ($attribute, $value, $onFailure) { // Premium
                if (strlen($value) > 25) {
                    $onFailure('Gross Premium should not exceed length of 25 characters');
                }
            },
            '*.16' => function ($attribute, $value, $onFailure) { // Notes
                if (strlen($value) > 200) {
                    $onFailure('Notes should not exceed length of 200 characters');
                }
            },
            '*.17' => function ($attribute, $value, $onFailure) { // Make
                if (strlen($value) > 50) {
                    $onFailure('Car Make should not exceed length of 50 characters');
                }
            },
            '*.18' => function ($attribute, $value, $onFailure) { // Model
                if (strlen($value) > 50) {
                    $onFailure('Car Model should not exceed length of 50 characters');
                }
            },
            '*.19' => function ($attribute, $value, $onFailure) { // Year
                if (strlen($value) > 4) {
                    $onFailure('Year of Manufacture should not exceed length of 4 characters');
                }
            },
        ];
    }
}
