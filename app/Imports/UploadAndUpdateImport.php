<?php

namespace App\Imports;

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\SkipPlansEnum;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use App\Traits\RenewalsImportTrait;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;

class UploadAndUpdateImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $renewalsUploadService;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;
    private $renewalsUploadLead;
    private $isSIC;

    public function __construct(RenewalsUploadService $renewalsUploadService, RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadService = $renewalsUploadService;
        $this->renewalsUploadLead = $renewalsUploadLead;
        $this->isSIC = $renewalsUploadLead->is_sic == 1 ? 'true' : 'false';
    }

    /**
     * validation rules for every column in a row.
     *
     * @return string[]
     */
    public function rules(): array
    {
        return $this->getRules();
    }

    /**
     * @return RenewalQuoteProcess
     */
    public function model(array $row)
    {
        $this->validCount++;

        $quoteData = $this->mapQuoteData($row);

        return new RenewalQuoteProcess([
            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
            'quote_type' => $quoteData['quote_type'],
            'policy_number' => $quoteData['policy_number'],
            'data' => $quoteData,
            'batch' => $quoteData['batch'],
            'status' => RenewalProcessStatuses::NEW,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'type' => RenewalsUploadType::UPDATE_LEADS,
        ]);
    }

    public function batchSize(): int
    {
        return 500;
    }

    /**
     * start import from row 2, first row have titles
     */
    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function getValidCount(): int
    {
        return $this->validCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * create columns schema, with index, title and rules to be validated for each column.
     *
     * @return array[]
     */
    public function getColumns()
    {
        $columns = [
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'nullable|max:100'],
            'email' => [
                'index' => 1,
                'title' => 'Customer Email',
                'rules' => [
                    'nullable', 'max:255', 'email:rfc,dns',
                    function ($attribute, $value, $onFailure) {
                        if ($value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $onFailure('The '.$attribute.' must contain a local part, @ symbol, and domain part (e.g., name@example.com).');
                        }
                    },
                ],
            ],
            'mobile_no' => ['index' => 2, 'title' => 'Customer Mobile', 'rules' => 'nullable|max:20'],
            'quote_type' => ['index' => 3, 'title' => 'Insurance Type', 'rules' => 'required|required|max:4'],
            'insurer' => ['index' => 4, 'title' => 'Insurance Provider', 'rules' => 'required|max:100'],
            'registration_type' => ['index' => 5, 'title' => 'Registration Type', 'rules' => 'nullable|max:100|in:'.implode(',', CarRegistrationType::getValues())],
            'vehicle_use' => ['index' => 6, 'title' => 'Vehicle Use', 'rules' => 'nullable|max:100|in:'.implode(',', CarVehicleUse::getValues())],
            'business_activity' => ['index' => 7, 'title' => 'Business Activity', 'rules' => 'nullable'],
            'driver_name' => ['index' => 8, 'title' => 'Driver Name', 'rules' => 'nullable|max:100'],

            'product_type' => ['index' => 9, 'title' => 'Product Type', 'rules' => 'required|max:100'],
            'advisor' => ['index' => 10, 'title' => 'Advisor Email', 'rules' => $this->isSIC == 'true' ? 'max:100' : 'required|max:100'],
            'policy_number' => ['index' => 11, 'title' => 'Policy Number', 'rules' => 'required|required|max:100'],
            'end_date' => ['index' => 12, 'title' => 'Policy End date', 'rules' => ['required', 'max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'batch' => ['index' => 13, 'title' => 'Batch', 'rules' => 'nullable|max:50'],
            'make' => ['index' => 14, 'title' => 'Car Make', 'rules' => ['nullable', 'max:50']],
            'model' => ['index' => 15, 'title' => 'Car Model', 'rules' => ['nullable', 'max:50']],
            'year' => ['index' => 16, 'title' => 'Model Year', 'rules' => ['nullable', 'max:4']],
            'dob' => ['index' => 17, 'title' => 'Date of Birth', 'rules' => ['max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'driving_experience' => ['index' => 18, 'title' => 'Driving Experience', 'rules' => ['max:50']],
            'nationality' => ['index' => 19, 'title' => 'Nationality', 'rules' => ['max:50']],
            'provider_name' => ['index' => 20, 'title' => 'Provider Name', 'rules' => 'nullable|max:100'],
            'plan_name' => ['index' => 21, 'title' => 'Plan Name', 'rules' => 'max:100'],
            'plan_type' => ['index' => 22, 'title' => 'Repair Type', 'rules' => 'max:100'],
            'claim_history' => ['index' => 23, 'title' => 'Claim History', 'rules' => 'max:50'],
            'nc_letter' => ['index' => 24, 'title' => 'NC Letter', 'rules' => 'max:3'],
            'insurer_quote_no' => ['index' => 25, 'title' => 'Insurer Quote No', 'rules' => 'max:50'],
            'car_value' => ['index' => 26, 'title' => 'Car Value (From Insurer)', 'rules' => 'nullable'],
            'premium' => ['index' => 27, 'title' => 'Renewal Premium', 'rules' => 'nullable|numeric'],
            'excess' => ['index' => 28, 'title' => 'Excess', 'rules' => 'nullable|numeric'],
            'ancillary_excess' => ['index' => 29, 'title' => 'Ancillary Excess', 'rules' => 'nullable|numeric'],
            'driver_cover' => ['index' => 30, 'title' => 'PAB Driver', 'rules' => ''],
            'driver_cover_amount' => ['index' => 31, 'title' => 'Amount - PAB Driver', 'rules' => 'nullable|numeric'],
            'passenger_cover' => ['index' => 32, 'title' => 'PAB Passenger', 'rules' => ''],
            'passenger_cover_amount' => ['index' => 33, 'title' => 'Amount - PAB Passenger', 'rules' => 'nullable|numeric'],
            'car_hire' => ['index' => 34, 'title' => 'Rent a car', 'rules' => ''],
            'car_hire_amount' => ['index' => 35, 'title' => 'Amount - Rent a car', 'rules' => 'nullable|numeric'],
            'oman_cover' => ['index' => 36, 'title' => 'Oman Cover', 'rules' => ''],
            'oman_cover_amount' => ['index' => 37, 'title' => 'Amount - Oman Cover', 'rules' => 'nullable|numeric'],
            'road_side_assistance' => ['index' => 38, 'title' => 'Road Side Assistance', 'rules' => ''],
            'road_side_assistance_amount' => ['index' => 39, 'title' => 'Amount - Road Side Assistance', 'rules' => 'nullable|numeric'],
            'year_of_first_registration' => ['index' => 40, 'title' => 'First Year of Registration', 'rules' => 'max:4'],
            'trim' => ['index' => 41, 'title' => 'Trim', 'rules' => 'max:20'],
            'registration_location' => ['index' => 42, 'title' => 'Registration Location', 'rules' => ['max:100']],
            'previous_advisor' => ['index' => 43, 'title' => 'Previous Advisor Email', 'rules' => 'nullable|max:100'],
            'notes' => ['index' => 44, 'title' => 'Notes', 'rules' => 'max:500'],
            'is_gcc' => ['index' => 45, 'title' => 'Is GCC', 'rules' => 'max:3'],

        ];

        if ($this->renewalsUploadLead->skip_plans != SkipPlansEnum::NON_GCC) {
            $columns['make']['rules'][] = 'nullable';
            $columns['model']['rules'][] = 'nullable';
            $columns['year']['rules'][] = 'nullable';
            $columns['registration_location']['rules'][] = 'nullable';
            $columns['car_value']['rules'] = 'required|numeric';
            $columns['claim_history']['rules'] = 'required|max:50';
            $columns['nc_letter']['rules'] = 'required|max:3';

        }

        return $columns;
    }

    /**
     * get all validation errors and store records in db along with errors.
     *
     * @return \Closure[]
     */
    public function registerEvents(): array
    {
        return [

            AfterImport::class => function (AfterImport $event) {
                $failed = [];
                foreach ($this->failures() as $failure) {
                    if (! isset($failed[$failure->row()])) {
                        $quoteData = $this->mapData($failure->values());
                        $failed[$failure->row()] = [
                            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
                            'quote_type' => $quoteData['quote_type'],
                            'policy_number' => $quoteData['policy_number'],
                            'data' => $quoteData,
                            'batch' => $quoteData['batch'],
                            'status' => RenewalProcessStatuses::VALIDATION_FAILED,
                            'type' => RenewalsUploadType::UPDATE_LEADS,
                        ];

                        $this->failedCount++;
                    }

                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                $failed = array_map([RenewalQuoteProcess::class, 'prepareForBulkInsert'], $failed);

                // Bulk insert failed records to reduce memory and improve performance
                if (! empty($failed)) {
                    RenewalQuoteProcess::insert($failed);
                }

                // Force garbage collection to free memory after processing chunk
                unset($failed);
                gc_collect_cycles();
            },
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get data from the validator - this is the data being validated
            $data = $validator->getData();
            // We need to check if the required fields exist and have proper values
            foreach ($data as $rowIndex => $row) {
                // Skip header row if needed
                if ($rowIndex == 0) {
                    continue;
                }
                // Check if registration_type is provided
                if (! isset($row[5]) || empty($row[5])) {
                    $validator->errors()->add(
                        $rowIndex.'.5',
                        'Registration Type is required.'
                    );
                }
                // Check registration_type and vehicle_use relationship
                if (isset($row[5]) && $row[5] == CarRegistrationType::COMPANY) {
                    if (isset($row[6]) && empty($row[6])) {
                        $validator->errors()->add(
                            $rowIndex.'.6',
                            'Vehicle Use is required.'
                        );
                    }
                }

                // Check vehicle_use and business_activity relationship
                if (isset($row[6]) && $row[6] == CarVehicleUse::COMMERCIAL) {
                    if (isset($row[7]) && empty($row[7])) {
                        $validator->errors()->add(
                            $rowIndex.'.7',
                            'Business Activity is required'
                        );
                    }
                }

                // Check vehicle_use and driver fields relationship
                if (isset($row[6]) && $row[6] == CarVehicleUse::PRIVATE) {
                    if (isset($row[8]) && empty($row[8])) {
                        $validator->errors()->add(
                            $rowIndex.'.8',
                            'Driver Name is required.'
                        );
                    }
                    if (isset($row[17]) && empty($row[17])) {
                        $validator->errors()->add(
                            $rowIndex.'.17',
                            'Date of Birth is required.'
                        );
                    }
                    if (isset($row[18]) && empty($row[18])) {
                        $validator->errors()->add(
                            $rowIndex.'.18',
                            'Driver Experience is required.'
                        );
                    }
                    if (isset($row[19]) && empty($row[19])) {
                        $validator->errors()->add(
                            $rowIndex.'.19',
                            'Nationality is required.'
                        );
                    }
                }
            }
        });
    }

}
