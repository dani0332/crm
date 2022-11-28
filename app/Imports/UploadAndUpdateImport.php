<?php

namespace App\Imports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\Customer;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class UploadAndUpdateImport implements ToModel, WithBatchInserts, WithStartRow, WithValidation, SkipsOnFailure, WithChunkReading, WithEvents
{
    use Importable, SkipsFailures, RegistersEventListeners;

    private $validCount = 0;
    private $failedCount = 0;
    private $renewalsUploadService;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;
    private $renewalsUploadLead;

    /**
     * @param  RenewalsUploadService  $renewalsUploadService
     * @param $renewalsUploadLead
     */
    public function __construct(RenewalsUploadService $renewalsUploadService, RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadService = $renewalsUploadService;
        $this->renewalsUploadLead = $renewalsUploadLead;
    }

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
            'type' => RenewalsUploadType::UPDATE_LEADS,
        ]);
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * start import from row 2, first row have titles
     *
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 2000;
    }

    /**
     * @return int
     */
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
        return  [
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'required|max:100|regex:/^[\pL\s\-]+$/u'],
            'email' => ['index' => 1, 'title' => 'Customer Email', 'rules' => 'required|max:255'],
            'mobile_no' => ['index' => 2, 'title' => 'Customer Mobile', 'rules' => 'required|max:100'],
            'quote_type' => ['index' => 3, 'title' => 'Insurance Type', 'rules' => 'required|required|max:4'],
            'insurer' => ['index' => 4, 'title' => 'Insurance Provider', 'rules' => 'required|max:100'],
            'product_type' => ['index' => 5, 'title' => 'Product Type', 'rules' => 'required|max:100'],
            'advisor' => ['index' => 6, 'title' => 'Advisor Email', 'rules' => 'required|max:100'],
            'policy_number' => ['index' => 7, 'title' => 'Policy Number', 'rules' => 'required|required|max:100'],
            'end_date' => ['index' => 8, 'title' => 'Policy End date', 'rules' => 'required|max:10', 'type' => 'date'],
            'batch' => ['index' => 9, 'title' => 'Batch', 'rules' => 'required|max:25'],
            'make' => ['index' => 10, 'title' => 'Car Make', 'rules' => 'required|max:50'],
            'model' => ['index' => 11, 'title' => 'Car Model', 'rules' => 'required|max:50'],
            'year' => ['index' => 12, 'title' => 'Model Year', 'rules' => 'required|max:4'],
            'dob' => ['index' => 13, 'title' => 'Date of Birth', 'rules' => 'required|max:10', 'type' => 'date'],
            'driving_experience' => ['index' => 14, 'title' => 'Driving Experience', 'rules' => 'required|max:10'],
            'nationality' => ['index' => 15, 'title' => 'Nationality', 'rules' => 'required|max:50'],
            'provider_name' => ['index' => 16, 'title' => 'Provider Name', 'rules' => 'max:100'],
            'plan_name' => ['index' => 17, 'title' => 'Plan Name', 'rules' => 'max:100'],
            'plan_type' => ['index' => 18, 'title' => 'Repair Type', 'rules' => 'max:100'],
            'claim_history' => ['index' => 19, 'title' => 'Claim History', 'rules' => 'required|max:50'],
            'nc_letter' => ['index' => 20, 'title' => 'NC Letter', 'rules' => 'max:3'],
            'insurer_quote_no' => ['index' => 21, 'title' => 'Insurer Quote No', 'rules' => 'max:50'],
            'car_value' => ['index' => 22, 'title' => 'Car Value (From Insurer)', 'rules' => 'nullable|numeric'],
            'premium' => ['index' => 23, 'title' => 'Renewal Premium', 'rules' => 'nullable|numeric'],
            'excess' => ['index' => 24, 'title' => 'Excess', 'rules' => 'nullable|numeric'],
            'ancillary_excess' => ['index' => 25, 'title' => 'Ancillary Excess', 'rules' => 'nullable|numeric'],
            'driver_cover' => ['index' => 26, 'title' => 'PAB Driver', 'rules' => ''],
            'driver_cover_amount' => ['index' => 27, 'title' => 'Amount - PAB Driver', 'rules' => 'nullable|numeric'],
            'passenger_cover' => ['index' => 28, 'title' => 'PAB Passenger', 'rules' => ''],
            'passenger_cover_amount' => ['index' => 29, 'title' => 'Amount - PAB Passenger', 'rules' => 'nullable|numeric'],
            'car_hire' => ['index' => 30, 'title' => 'Rent a car', 'rules' => ''],
            'car_hire_amount' => ['index' => 31, 'title' => 'Amount - Rent a car', 'rules' => 'nullable|numeric'],
            'oman_cover' => ['index' => 32, 'title' => 'Oman Cover', 'rules' => ''],
            'oman_cover_amount' => ['index' => 33, 'title' => 'Amount - Oman Cover', 'rules' => 'nullable|numeric'],
            'road_side_assistance' => ['index' => 34, 'title' => 'Road Side Assistance', 'rules' => ''],
            'road_side_assistance_amount' => ['index' => 35, 'title' => 'Amount - Road Side Assistance', 'rules' => 'nullable|numeric'],
            'year_of_first_registration' => ['index' => 36, 'title' => 'First Year of Registration', 'rules' => 'max:4'],
            'trim' => ['index' => 37, 'title' => 'Trim', 'rules' => 'max:20'],
            'registration_location' => ['index' => 38, 'title' => 'Registration Location', 'rules' => 'required|max:100'],
            'previous_advisor' => ['index' => 39, 'title' => 'Previous Advisor Email', 'rules' => 'max:100'],
            'notes' => ['index' => 40, 'title' => 'Notes', 'rules' => 'max:200'],
        ];
    }

    /**
     * custom validation message
     *
     * @return string[]
     */
    public function customValidationMessages()
    {
        return [
            '0.regex' => ':attribute should only be in letters - no numbers allowed.',
        ];
    }

    /**
     * map row with keys.
     *
     * @param $row
     * @return array
     */
    public function mapQuoteData($row)
    {
        $columns = $this->getColumns();

        $quoteData = [];
        foreach ($columns as $key => $column) {
            if (! empty($column['type']) && $column['type'] == 'date') {
                if (strpos($row[$column['index']], '/')) {
                    $quoteData[$key] = Carbon::createFromFormat('d/m/Y', $row[$column['index']])->format('d/m/Y');
                } else {
                    $quoteData[$key] = Carbon::instance(Date::excelToDateTimeObject((float) $row[$column['index']]))->format('d/m/Y');
                }
            } else {
                $quoteData[$key] = $row[$column['index']] ?? null;
            }
        }

        return $quoteData;
    }

    /**
     * Attributes Mapping, pluck titles from columns and these will be used in validation as field name.
     *
     * @return string[] e.g 0 => Customer Name, 1 => Customer Email
     */
    public function customValidationAttributes()
    {
        $colums = collect($this->getColumns());

        return $colums->pluck('title', 'index')->toArray();
    }

    /**
     * validation rules for every column in a row.
     *
     * @return string[]
     */
    public function rules(): array
    {
        $rules = [];
        $columns = collect($this->getColumns())->pluck('rules', 'index');

        $columns->each(function ($item, $index) use (&$rules) {
            $rules['*.'.$index] = $item;
        });

        return $rules;
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
                        $quoteData = $this->mapQuoteData($failure->values());
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

                //todo: convert this to bulk insert
                foreach ($failed as $failedRecord) {
                    RenewalQuoteProcess::create($failedRecord);
                }
            },
        ];
    }
}
