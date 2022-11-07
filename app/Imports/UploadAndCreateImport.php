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
use Maatwebsite\Excel\Concerns\OnEachRow;
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

class UploadAndCreateImport implements ToModel, WithBatchInserts, WithStartRow, WithValidation, SkipsOnFailure, WithChunkReading, WithEvents
{
    use Importable, SkipsFailures, RegistersEventListeners;

    private $validCount = 0;
    private $failedCount = 0;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;
    private $renewalsUploadLead;

    /**
     * @param  RenewalsUploadService  $renewalsUploadService
     * @param $renewalsUploadLead
     */
    public function __construct(RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadLead = $renewalsUploadLead;
    }

    /**
     * @param array $row
     * @return User
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
            'type' => RenewalsUploadType::CREATE_LEADS,
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
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'required|max:100'],
            'email' => ['index' => 1, 'title' => 'Customer Email', 'rules' => 'required|max:255'],
            'quote_type' => ['index' => 2, 'title' => 'Type', 'rules' => 'required|max:4'],
            'insurer' => ['index' => 3, 'title' => 'Insurer', 'rules' => 'required|max:100'],
            'product' => ['index' => 4, 'title' => 'Product', 'rules' => 'required|max:100'],
            'product_type' => ['index' => 5, 'title' => 'Product Type', 'rules' => 'max:100'],
            'source' => ['index' => 6, 'title' => 'Sales Channel', 'rules' => 'max:100'],
            'mobile_no' => ['index' => 7, 'title' => 'Customer Mobile', 'rules' => 'max:100'],
            'advisor' => ['index' => 8, 'title' => 'Advisor Email', 'rules' => 'max:100'],
            'previous_advisor' => ['index' => 9, 'title' => 'Previous Advisor Email', 'rules' => 'max:100'],
            'policy_number' => ['index' => 10, 'title' => 'Policy', 'rules' => 'required|max:100'],
            'batch' => ['index' => 11, 'title' => 'Batch', 'rules' => 'required|max:25'],
            'start_date' => ['index' => 12, 'title' => 'Start Date', 'rules' => 'max:25', 'type' => 'date'], //date_format:d/m/Y
            'end_date' => ['index' => 13, 'title' => 'End Date', 'rules' => 'required|max:25', 'type' => 'date'], //date_format:d/m/Y
            'object' => ['index' => 14, 'title' => 'Object', 'rules' => 'max:200'],
            'premium' => ['index' => 15, 'title' => 'Gross Premium', 'rules' => 'max:25'],
            'notes' => ['index' => 16, 'title' => 'Notes', 'rules' => 'max:200'],
            'make' => ['index' => 17, 'title' => 'Make', 'rules' => 'max:50'],
            'model' => ['index' => 18, 'title' => 'Model', 'rules' => 'max:50'],
            'year' => ['index' => 19, 'title' => 'Year', 'rules' => 'max:4'],
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
        $fields = $this->getColumns();

        $quoteData = [];
        foreach ($fields as $key => $field) {
            if (! empty($field['type']) && $field['type'] == 'date') {
                $quoteData[$key] = Carbon::instance(Date::excelToDateTimeObject($row[$field['index']]))->format('d/m/Y');
            } else {
                $quoteData[$key] = $row[$field['index']];
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
                            'type' => RenewalsUploadType::CREATE_LEADS,
                        ];

                        $this->failedCount++;
                    }
                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                foreach ($failed as $failedRecord) {
                    RenewalQuoteProcess::create($failedRecord);
                }
            },
        ];
    }
}
