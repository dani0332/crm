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
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'max:100|regex:/^[\pL\s]+$/u'],
            'advisor' => ['index' => 1, 'title' => 'Advisor Email', 'rules' => 'max:100'],
            'policy_number' => ['index' => 2, 'title' => 'Policy', 'rules' => 'required|max:100'],
            'insurer' => ['index' => 3, 'title' => 'Insurer', 'rules' => 'required|max:100'],
            'batch' => ['index' => 4, 'title' => 'Batch', 'rules' => 'max:25'],
            'end_date' => ['index' => 5, 'title' => 'End Date', 'rules' => 'required|max:10', 'type' => 'date'],
            'make' => ['index' => 6, 'title' => 'Make', 'rules' => 'max:50'],
            'model' => ['index' => 7, 'title' => 'Model', 'rules' => 'max:50'],
            'year' => ['index' => 8, 'title' => 'Year', 'rules' => 'max:4'],
            'email' => ['index' => 9, 'title' => 'Customer Email', 'rules' => 'max:255'],
            'mobile_no' => ['index' => 10, 'title' => 'Customer Mobile', 'rules' => 'max:100'],
            'dob' => ['index' => 11, 'title' => 'Date of Birth', 'rules' => 'max:10', 'type' => 'date'],
            'driving_experience' => ['index' => 12, 'title' => 'Driving Experience', 'rules' => 'max:10'],
            'provider_name' => ['index' => 13, 'title' => 'Provider Name', 'rules' => 'max:100'],
            'plan_name' => ['index' => 14, 'title' => 'Plan Name', 'rules' => 'max:100'],
            'plan_type' => ['index' => 15, 'title' => 'Plan Type', 'rules' => 'max:100'],
            'claim_history' => ['index' => 16, 'title' => 'Claim History', 'rules' => 'max:50'],
            'car_value' => ['index' => 17, 'title' => 'Car Value', 'rules' => 'max:20'],
            'nationality' => ['index' => 18, 'title' => 'Nationality', 'rules' => 'max:20'],
            'premium' => ['index' => 19, 'title' => 'Renewal Premium', 'rules' => 'max:20'],
            'excess' => ['index' => 20, 'title' => 'Excess', 'rules' => 'max:20'],
            'trim' => ['index' => 21, 'title' => 'Trim', 'rules' => 'max:20'],
            'quote_type' => ['index' => 22, 'title' => 'Type', 'rules' => 'required|max:4'],
            'product_type' => ['index' => 23, 'title' => 'Product Type', 'rules' => 'max:100'],
            'registration_location' => ['index' => 24, 'title' => 'Registration Location', 'rules' => 'max:100'],
            'previous_advisor' => ['index' => 25, 'title' => 'Previous Advisor Email', 'rules' => 'max:100'],
            'notes' => ['index' => 26, 'title' => 'Notes', 'rules' => 'max:200'],
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

                if(strpos($row[$column['index']], '/')) {
                    $quoteData[$key] = Carbon::createFromFormat('d/m/Y', $row[$column['index']])->format('d/m/Y');
                }
                else {
                    $quoteData[$key] = Carbon::instance(Date::excelToDateTimeObject( (float) $row[$column['index']]))->format('d/m/Y');
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
