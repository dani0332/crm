<?php

namespace App\Imports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use App\Traits\RenewalsImportTrait;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Row;

class UploadAndCreateImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;
    private $renewalsUploadLead;

    /**
     * @param  RenewalsUploadService  $renewalsUploadService
     */
    public function __construct(RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadLead = $renewalsUploadLead;
    }

    public function onRow(Row $row)
    {
        $this->validCount++;
        $row = $row->toArray();

        $quoteData = $this->mapQuoteData($row);

        return RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
            'quote_type' => $quoteData['quote_type'],
            'policy_number' => $quoteData['policy_number'],
            'data' => $quoteData,
            'batch' => $quoteData['batch'],
            'status' => RenewalProcessStatuses::NEW,
            'type' => RenewalsUploadType::CREATE_LEADS,
        ]);
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    /**
     * start import from row 2, first row have titles
     */
    public function startRow(): int
    {
        return 2;
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
        return [
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'required|max:100'],
            'email' => [
                'index' => 1,
                'title' => 'Customer Email',
                'rules' => [
                    'required', 'max:255',
                    function ($attribute, $value, $onFailure) {
                        if ($value == 0 || $value == '0') {
                            $onFailure('The '.$attribute.' cannot be 0.');
                        }
                    },
                ],
            ],
            'mobile_no' => [
                'index' => 2,
                'title' => 'Customer Mobile',
                'rules' => [
                    'required', 'max:100',
                    function ($attribute, $value, $onFailure) {
                        if ($value == 0 || $value == '0') {
                            $onFailure('The '.$attribute.' cannot be 0.');
                        }
                    },
                ],
            ],
            'quote_type' => ['index' => 3, 'title' => 'Insurance Type', 'rules' => 'required|max:4'],
            'insurer' => ['index' => 4, 'title' => 'Insurance Provider', 'rules' => 'required|max:100'],
            'product' => ['index' => 5, 'title' => 'Product', 'rules' => 'required|max:100'],
            'product_type' => ['index' => 6, 'title' => 'Product Type', 'rules' => 'max:100'],
            'advisor' => ['index' => 7, 'title' => 'Advisor Email', 'rules' => 'max:100'],
            'policy_number' => ['index' => 8, 'title' => 'Policy Number', 'rules' => 'required|max:100'],
            'start_date' => ['index' => 9, 'title' => 'Policy Start Date', 'rules' => ['max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'end_date' => ['index' => 10, 'title' => 'Policy End date', 'rules' => ['required', 'max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'batch' => ['index' => 11, 'title' => 'Batch', 'rules' => 'max:50'],
            'make' => ['index' => 12, 'title' => 'Car Make', 'rules' => 'max:50'],
            'model' => ['index' => 13, 'title' => 'Car Model', 'rules' => 'max:50'],
            'year' => ['index' => 14, 'title' => 'Model Year', 'rules' => 'max:4'],
            'previous_advisor' => ['index' => 15, 'title' => 'Previous Advisor Email', 'rules' => 'nullable|max:100'],
            'object' => ['index' => 16, 'title' => 'Object', 'rules' => 'max:200'],
            'premium' => ['index' => 17, 'title' => 'Gross Premium', 'rules' => 'nullable|numeric'],
            'source' => ['index' => 18, 'title' => 'Sales Channel', 'rules' => 'max:100'],
            'notes' => ['index' => 19, 'title' => 'Notes', 'rules' => 'max:500'],
            'plan_name' => ['index' => 20, 'title' => 'Plan Name', 'rules' => 'max:100'],
        ];
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
