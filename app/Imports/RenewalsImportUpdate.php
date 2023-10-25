<?php

namespace App\Imports;

use App\Enums\QuoteTypeShortCode;
use App\Jobs\RenewalImportJob;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class RenewalsImportUpdate implements OnEachRow, SkipsOnFailure, WithChunkReading, WithStartRow, WithValidation
{
    use Importable, SkipsFailures;

    private $rows = 0;
    private $renewalsUploadService;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;

    public function __construct(RenewalsUploadService $renewalsUploadService, $fileName, $renewalImportCode, $uploadType)
    {
        $this->renewalsUploadService = $renewalsUploadService;
        $this->fileName = $fileName;
        $this->renewalImportCode = $renewalImportCode;
        $this->uploadType = $uploadType;
    }

    public function onRow(Row $row)
    {
        $this->rows++;
        $row = $row->toArray();
        $quoteType = $row[0];
        $policy = $row[4];

        if (! empty($policy) && $quoteType == QuoteTypeShortCode::CAR) {
            $quoteData = 0;

            // product information
            $productType = $row[1];

            // car quote information
            $carMake = $row[7];
            $carModel = $row[8];
            $carYear = $row[9];

            // other information
            $advisor = preg_replace('/\s/', '', strtolower(trim(ltrim(rtrim($row[2])))));
            $previousAdvisor = preg_replace('/\s/', '', strtolower(trim(ltrim(rtrim($row[3])))));
            $batch = $row[5];
            $notes = $row[6];

            $quoteData = (object) [
                'type' => $quoteType,
                'product_type' => $productType,
                'make' => $carMake,
                'model' => $carModel,
                'year' => $carYear,
                'advisor' => $advisor,
                'pAdvisor' => $previousAdvisor,
                'policy' => $policy,
                'batch' => $batch,
                'notes' => $notes,
            ];

            dispatch(new RenewalImportJob($quoteData, $quoteType, $this->renewalsUploadService, $this->fileName, $this->renewalImportCode, $this->uploadType, Auth::user()->id));
        }
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
            '*.0' => function ($attribute, $value, $onFailure) { // Type
                if (! $value) {
                    $onFailure('Type of quote is required');
                }
                if (strlen($value) > 4) {
                    $onFailure('Type of quote should not exceed length of 4 characters');
                }
            },
            '*.1' => function ($attribute, $value, $onFailure) { // Product Type
                if (strlen($value) > 50) {
                    $onFailure('Product Type should not exceed length of 50 characters');
                }
                if (strlen($value) > 0 && $value != 'Comprehensive' && $value != 'Third Party Only') {
                    $onFailure('Product Type should be either Comprehensive or Third Party  Only');
                }
            },
            '*.2' => function ($attribute, $value, $onFailure) { // Advisor Email
                if (strlen($value) > 100) {
                    $onFailure('Advisor Email should not exceed length of 100 characters');
                }
            },
            '*.3' => function ($attribute, $value, $onFailure) { // Previous Advisor Email
                if (strlen($value) > 100) {
                    $onFailure('Previous Advisor Email should not exceed length of 100 characters');
                }
            },
            '*.4' => function ($attribute, $value, $onFailure) { // Policy
                if (! $value) {
                    $onFailure('Policy is required');
                }
                if (strlen($value) > 100) {
                    $onFailure('Policy should not exceed length of 100 characters');
                }
            },
            '*.5' => function ($attribute, $value, $onFailure) { // Batch
                if (strlen($value) > 25) {
                    $onFailure('Batch should not exceed length of 25 characters');
                }
            },
            '*.6' => function ($attribute, $value, $onFailure) { // Notes
                if (strlen($value) > 200) {
                    $onFailure('Notes should not exceed length of 200 characters');
                }
            },
            '*.7' => function ($attribute, $value, $onFailure) { // Make
                if (strlen($value) > 50) {
                    $onFailure('Car Make should not exceed length of 50 characters');
                }
            },
            '*.8' => function ($attribute, $value, $onFailure) { // Model
                if (strlen($value) > 50) {
                    $onFailure('Car Model should not exceed length of 50 characters');
                }
            },
            '*.9' => function ($attribute, $value, $onFailure) { // Year
                if (strlen($value) > 4) {
                    $onFailure('Year of Manufacture should not exceed length of 4 characters');
                }
            },
        ];
    }
}
