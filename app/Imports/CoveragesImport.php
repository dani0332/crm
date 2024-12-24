<?php

namespace App\Imports;

use App\Models\RateCoverageProcess;
use App\Models\RateCoverageUpload;
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

    public function __construct(RateCoverageUpload $uploadCoverages)
    {
        $this->uploadCoverages = $uploadCoverages;
    }

    public function onRow(Row $row)
    {
        $this->validCount++;
        $rowNumber = $row->getIndex();
        $row = $row->toArray();

        $coverageData = $this->mapQuoteData($row);
        $coverageData['row_number'] = $rowNumber;

        return RateCoverageProcess::create([
            'rate_coverage_id' => $this->uploadCoverages->id,
            'data' => $coverageData,
            'type' => 'coverage',
        ]);
    }

    public function chunkSize(): int
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
            'plan_code' => ['index' => 6, 'title' => 'plan_code', 'rules' => 'required'],
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
                        $quoteData = $this->mapQuoteData($failure->values());
                        if (empty($quoteData)) {
                            continue; // Skip empty quote data
                        }
                        $rowNumber = $failure->row();
                        $quoteData['row_number'] = $rowNumber;
                        // info('DATAAA', [$quoteData]);
                        $failed[$failure->row()] = [
                            'rate_coverage_id' => $this->uploadCoverages->id,
                            'data' => $quoteData,
                            'type' => 'coverage',
                        ];

                        $this->failedCount++;
                    }
                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                foreach ($failed as $failedRecord) {
                    info('Record Added in Coverage Import');
                    RateCoverageProcess::create($failedRecord);
                }
            },
        ];
    }

    protected function mapQuoteData(array $row): array
    {
        $data = [
            'code' => $row[0] ?? null,
            'text' => $row[1] ?? null,
            'description' => $row[2] ?? null,
            'value' => $row[3] ?? null,
            'type' => $row[4] ?? null,
            'is_northern' => $row[5] ?? null,
            'plan_code' => $row[6] ?? null,
        ];

        $filteredData = array_filter($data, function ($value) {
            return ! is_null($value) && $value !== ''; // Exclude nulls and empty strings
        });

        return ! empty($filteredData) ? $filteredData : [];
    }

}
