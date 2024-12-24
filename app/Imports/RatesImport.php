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

class RatesImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $uploadRate;

    public function __construct(RateCoverageUpload $uploadRate)
    {
        $this->uploadRate = $uploadRate;
    }

    public function onRow(Row $row)
    {
        $rowNumber = $row->getIndex();
        $row = $row->toArray();

        $rateData = $this->mapQuoteData($row);

        // Only proceed if rateData is valid
        if (! empty($rateData)) {
            $this->validCount++;
            $rateData['row_number'] = $rowNumber;
            RateCoverageProcess::create([
                'rate_coverage_id' => $this->uploadRate->id,
                'data' => $rateData,
                'type' => 'rates',
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function startRow(): int
    {
        return 2; // Starting from row 2 to skip headers
    }

    public function getValidCount(): int
    {
        return $this->validCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getColumns()
    {
        return [
            'eligibility_code' => ['index' => 5, 'title' => 'eligibility_code', 'rules' => 'required'],
            'plan_code' => ['index' => 6, 'title' => 'plan_code', 'rules' => 'required'],
            'copayment_code' => ['index' => 7, 'title' => 'copayment_code', 'rules' => 'required'],
        ];
    }

    public function rules(): array
    {
        return $this->getRules();
    }

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
                        $failed[$failure->row()] = [
                            'rate_coverage_id' => $this->uploadRate->id,
                            'data' => $quoteData,
                            'type' => 'rate',
                        ];

                        $this->failedCount++;
                    }
                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                foreach ($failed as $failedRecord) {
                    info('Record Added in Rate Import');
                    RateCoverageProcess::create($failedRecord);
                }
            },
        ];
    }

    protected function mapQuoteData(array $row): array
    {
        $data = [
            'is_northern' => $row[0] ?? null,
            'min_age' => $row[1] ?? null,
            'max_age' => $row[2] ?? null,
            'gender' => $row[3] ?? '',
            'premium' => $row[4] ?? null,
            'eligibility_code' => $row[5] ?? null,
            'plan_code' => $row[6] ?? null,
            'copayment_code' => $row[7] ?? null,
        ];

        $filteredData = array_filter($data, function ($value) {
            return ! is_null($value) && $value !== '';
        });

        // Only return filtered data if it's not empty
        return ! empty($filteredData) ? $filteredData : [];
    }
}
