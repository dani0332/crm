<?php
namespace App\Imports;

use App\Models\RateCoveragesProcess;
use App\Models\RatesCoveragesUpload;
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

    public function __construct(RatesCoveragesUpload $uploadRate)
    {
        $this->uploadRate = $uploadRate;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $rateData = $this->mapQuoteData($row);

        // Only proceed if rateData is valid
        if (!empty($rateData)) {
            $this->validCount++;
            RateCoveragesProcess::create([
                'rate_coverage_id' => $this->uploadRate->id,
                'data' => $rateData,
                'type' => 'rates',
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 2000;
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
            'is_northern' => ['index' => 0, 'title' => 'is_northern', 'rules' => 'required'],
            'min_age' => ['index' => 1, 'title' => 'min_age', 'rules' => 'required'],
            'max_age' => ['index' => 2, 'title' => 'max_age', 'rules' => 'required'],
            'gender' => ['index' => 3, 'title' => 'gender', 'rules' => 'required'],
            'premium' => ['index' => 4, 'title' => 'premium', 'rules' => 'required'],
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
                    if (!isset($failed[$failure->row()])) {
                        $quoteData = $this->mapQuoteData($failure->values());
                        if (empty($quoteData)) {
                            continue; // Skip empty quote data
                        }
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
                    RateCoveragesProcess::create($failedRecord);
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
            'gender' => $row[3] ?? null,
            'premium' => $row[4] ?? null,
            'eligibility_code' => $row[5] ?? null,
            'plan_code' => $row[6] ?? null,
            'copayment_code' => $row[7] ?? null,
        ];

        // Return empty if crucial fields are missing
        if (empty($data['plan_code']) || empty($data['eligibility_code']) || empty($data['copayment_code'])) {
            return [];
        }

        $filteredData = array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });

        // Only return filtered data if it's not empty
        return !empty($filteredData) ? $filteredData : [];
    }
}
