<?php

namespace App\Imports;

use App\Jobs\RenewalImportJob;
use App\Models\Customer;
use App\Services\RenewalsUploadService;
use App\Services\CustomerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RenewalsImport implements OnEachRow, WithStartRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use Importable, SkipsFailures;
    private $rows = 0;
    private $renewalsUploadService;
    private $totalRows;
    private $fileName;
    function __construct(RenewalsUploadService $renewalsUploadService, $fileName)
    {
        $this->renewalsUploadService = $renewalsUploadService;
        $this->fileName = $fileName;
    }

    /**
    * @param Row $row
    */
    public function onRow(Row $row)
    {
        ++$this->rows;
        $row = $row->toArray();

        $qouteType = $row[2];

        $email = strtolower(trim(ltrim(rtrim($row[1]))));;
        if($email != null) {
            $quoteData = 0;

            // customer information
            $customerName = explode(" ", $row[0], 2);
            $lastName = "";
            if (!empty($customerName[1])) {
                $firstName = $customerName[0];
                $lastName = $customerName[1];
            }
            else {
                $firstName = $row[0];
                $lastName = "";
            }

            // product information
            $insurer = $row[3];
            $product = $row[4];
            $productType = $row[5];
            $source = $row[6];

            // car quote information
            $carMake = $row[17];
            $carModel = $row[18];
            $carYear = $row[19];

            // other information
            $customerPhone = $row[7];
            $advisor = $row[8];
            $previousAdvisor = $row[9];
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
            $notes = $emailResult['1'] != '' ? $notes . $emailResult['1'] : $notes; // if the notes variable is not empty, add the notes from the email sanitization to the notes variable
            $notes = $phoneResult['1'] != '' ? $notes . $phoneResult['1'] : $notes; // if the notes variable is not empty, add the notes from the phone sanitization to the notes variable

            $findCustomerByEmail = CustomerService::getCustomerByEmail($email);
            if($findCustomerByEmail->isEmpty()) {
                $newCustomer = new Customer([
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "email" => $email,
                    "mobile_no" => $customerPhone,
                    "has_alfred_access" => false,
                    "has_reward_access" => false,
                ]);
                $newCustomer->save();
                $customerId = $newCustomer->getAttribute('id');
                $quoteData = (object) array(
                    "customer_id" => $customerId,
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "email" => $email,
                    "phoneNumber" => $customerPhone,
                    "insurer" => $insurer,
                    "product" => $product,
                    "product_type" => $productType,
                    "source" => $source,
                    "make" => $carMake,
                    "model" => $carModel,
                    "year" => $carYear,
                    "advisor" => $advisor,
                    "pAdvisor" => $previousAdvisor,
                    "policy" => $policy,
                    "batch" => $batch,
                    "startDate" => $startDate,
                    "endDate" => $endDate,
                    "object" => $object,
                    "gross_premium" => $premium,
                    "notes" => $notes,
                );
            }
            else {
                $customer = $findCustomerByEmail->first();
                $quoteData = (object) array(
                    "customer_id" => $customer->id,
                    "first_name" => $customer->first_name,
                    "last_name" => $customer->last_name,
                    "email" => $customer->email,
                    "phoneNumber" => $customerPhone,
                    "insurer" => $insurer,
                    "product" => $product,
                    "product_type" => $productType,
                    "source" => $source,
                    "make" => $carMake,
                    "model" => $carModel,
                    "year" => $carYear,
                    "advisor" => $advisor,
                    "pAdvisor" => $previousAdvisor,
                    "policy" => $policy,
                    "batch" => $batch,
                    "startDate" => $startDate,
                    "endDate" => $endDate,
                    "object" => $object,
                    "gross_premium" => $premium,
                    "notes" => $notes,
                );
            }
            dispatch(new RenewalImportJob($quoteData, $qouteType, $this->renewalsUploadService, $this->fileName));
        }
    }

    function sanitizePhoneNumber($phone)
    {
        $delimiterArray = [',', ':', '/', ';', '-'];
        $phone = str_replace(' ', '', $phone); // Replaces all spaces with hyphens.
        $cleanPhone = '';
        $notes = '';
        foreach ($delimiterArray as $delimiter) {
            if(strpos($phone, $delimiter) !== false) {
                $phoneNumberArray = explode($delimiter, $phone);
                $cleanPhone = preg_replace('/[^A-Za-z0-9\-]/', '', $phoneNumberArray[0]); // Removes special chars.
                $notes = " - Additional phone numbers from phone column : ". $phoneNumberArray[1];
                break;
            }
            else{
                $cleanPhone = $phone;
            }
        }
        return [$cleanPhone, $notes];
    }

    function sanitizeEmail($email)
    {
        $delimiterArray = [',', ':', '/', ';', '-'];	// delimiters
        $cleanEmail = '';
        $notes = '';
        foreach ($delimiterArray as $delimiter) {
            if(strpos($email, $delimiter) !== false) {
                $emailArray = explode($delimiter, $email);
                $cleanEmail = $emailArray[0];
                $notes = " - Additional Emails from email column : ". $emailArray[1];
                break;
            }
            else{
                $cleanEmail = $email;
            }
        }
        return [$cleanEmail, $notes];
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
            // Customer Name
            '*.0' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Customer Name is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Customer Name should not exceed length of 100 characters');
                }
            },
            // Customer Email
            '*.1' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Customer Email is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Customer Email should not exceed length of 100 characters');
                }
            },
            // Type
            '*.2' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Type of quote is required');
                }
                if(strlen($value) > 4) {
                    $onFailure('Type of quote should not exceed length of 4 characters');
                }
            },
            // Insurer
            '*.3' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Insurer is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Insurer should not exceed length of 100 characters');
                }
            },
            // Product
            '*.4' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Product is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Product should not exceed length of 100 characters');
                }
            },
            // Product Type
            '*.5' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 100) {
                    $onFailure('Product Type should not exceed length of 100 characters');
                }
                if($value != 'Comprehensive' && $value != 'Third Party Only') {
                    $onFailure('Product Type should be either Comprehensive or Third Party  Only');
                }
            },
            // Sales Channel
            '*.6' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Sales Channel is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Sales Channel should not exceed length of 100 characters');
                }
            },
            // Customer Mobile
            '*.7' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 100) {
                    $onFailure('Customer Mobile should not exceed length of 100 characters');
                }
            },
            // Advisor Email
            '*.8' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 100) {
                    $onFailure('Advisor Email should not exceed length of 100 characters');
                }
            },
            // Previous Advisor Email
            '*.9' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 100) {
                    $onFailure('Previous Advisor Email should not exceed length of 100 characters');
                }
            },
            // Policy
            '*.10' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('Policy is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('Policy should not exceed length of 100 characters');
                }
            },
            // Batch
            '*.11' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 25) {
                    $onFailure('Batch should not exceed length of 25 characters');
                }
            },
            // Start Date
            '*.12' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 100) {
                    $onFailure('Start Date should not exceed length of 100 characters');
                }
            },
            // End Date
            '*.13' => function($attribute, $value, $onFailure) {
                if(!$value) {
                    $onFailure('End Date is required');
                }
                if(strlen($value) > 100) {
                    $onFailure('End Date should not exceed length of 100 characters');
                }
            },
            // Object
            '*.14' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 200) {
                    $onFailure('Object should not exceed length of 200 characters');
                }
            },
            // Premium
            '*.15' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 25) {
                    $onFailure('Gross Premium should not exceed length of 25 characters');
                }
            },
            // Notes
            '*.16' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 200) {
                    $onFailure('Notes should not exceed length of 200 characters');
                }
            },
            // Make
            '*.17' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 50) {
                    $onFailure('Car Make should not exceed length of 25 characters');
                }
            },
            // Model
            '*.18' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 50) {
                    $onFailure('Car Model should not exceed length of 25 characters');
                }
            },
            // Year
            '*.19' => function($attribute, $value, $onFailure) {
                if(strlen($value) > 4) {
                    $onFailure('Year of Manufacture should not exceed length of 4 characters');
                }
            },
        ];
    }
}
