<?php

namespace App\Imports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RateCoveragesProcess;
use App\Models\RatesCoveragesUpload;
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

class CoveragesImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $totalRows;
    private $fileName;
    private $uploadType;
    private $uploadCoverages;

    public function __construct(RatesCoveragesUpload $uploadCoverages)
    {
        $this->uploadCoverages = $uploadCoverages;
    }

    public function onRow(Row $row)
    {
        $this->validCount++;
        $row = $row->toArray();

        $quoteData = $this->mapQuoteData($row);

        return RateCoveragesProcess::create([
            'rate_coverage_id' => $this->uploadCoverages->id,
            'data' => $quoteData,
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
            'code' => ['index' => 0, 'title' => 'Code', 'rules' => 'required'],
            'text' => ['index' => 1, 'title' => 'Text', 'rules' => 'required'],
            'description' => ['index' => 2, 'title' => 'Description', 'rules' => 'required'],
            'value' => ['index' => 3, 'title' => 'Value', 'rules' => 'required'],
            'type' => ['index' => 4, 'title' => 'Type', 'rules' => 'required'],
            'is_northern' => ['index' => 5, 'title' => 'Is Northern', 'rules' => 'required'],
            'plan_code' => ['index' => 6, 'title' => 'Plan Code', 'rules' => 'required'],
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
                            'rate_coverage_id' => $this->uploadCoverages->id,
                            'data' => $quoteData,
                            'type' => RenewalsUploadType::CREATE_LEADS,
                        ];

                        $this->failedCount++;
                    }
                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                foreach ($failed as $failedRecord) {
                    RateCoveragesProcess::create($failedRecord);
                }
            },
        ];
    }
}
