<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Enums\RateCoverageEnum;
use App\Imports\CoveragesImport;
use App\Imports\RatesImport;
use App\Jobs\UploadCoveragesJob;
use App\Jobs\UploadRatesJob;
use App\Models\RateCoverageProcess;
use App\Models\RateCoverageUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RateCoverageUploadService
{
    public function uploadFile()
    {
        $path = 'ratings/health';
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $datetime = date('Y-m-d_H-i-s');
        $azureFileName = $datetime.'_'.$fileName;

        $azureFilePath = request()->file('file_name')->storeAs($path, $azureFileName, 'azureIM');
        info('File Save in Azure', [$azureFilePath]);

        return [
            'file_name' => $fileName,
            'azure_file_path' => $azureFilePath,
        ];
    }

    public function createCoverages($uploadedFile)
    {
        $uploadLeadData = [
            'file_name' => $uploadedFile['file_name'],
            'file_path' => $uploadedFile['azure_file_path'],
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'type' => RateCoverageEnum::COVERAGES,
        ];
        info('Coverage Record Created in DB');

        return RateCoverageUpload::create($uploadLeadData);
    }

    public function coveragesUploadCreate($data)
    {
        $uploadedFile = $this->uploadFile();

        $uploadCoverages = $this->createCoverages($uploadedFile);

        UploadCoveragesJob::dispatch($uploadCoverages);
    }

    public function processUploadCoverages(RateCoverageUpload $uploadCoverages)
    {
        $logPrefix = 'UAC FN: processUploadCreate CoverageId: '.$uploadCoverages->id.' FileName: '.$uploadCoverages->file_name;

        try {
            // Set status to IN_PROGRESS
            $uploadCoverages->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $uploadCoverages = DB::transaction(function () use ($uploadCoverages) {
                // Start file import
                $uploadRecord = new CoveragesImport($uploadCoverages);
                $uploadRecord->import($uploadCoverages->file_path, 'azureIM');

                $rateCoverageProcesses = RateCoverageProcess::where('rate_coverage_id', $uploadCoverages->id)->get();

                $validDataCount = 0;
                $failedDataCount = 0;

                foreach ($rateCoverageProcesses as $process) {
                    $data = $process->data;

                    if (empty($data['plan_code'])) {
                        $failedDataCount++;

                        continue;
                    } else {
                        $validDataCount++;
                    }
                }
                $uploadCoverages->update([
                    'cannot_upload' => $failedDataCount,
                    'good' => $validDataCount,
                    'total_records' => $validDataCount + $failedDataCount,
                ]);

                return $uploadCoverages;
            });
            if ($uploadCoverages) {
                $this->createCoveragesData($uploadCoverages);
            }

            info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            // Update the status to FAILED in case of an error
            $uploadCoverages->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.' Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

    public function createCoveragesData($uploadCoverages)
    {
        // Delete existing records
        $planCodes = RateCoverageProcess::where('rate_coverage_id', $uploadCoverages->id)->pluck('data')->map(function ($data) {
            if (is_string($data)) {
                $decodedData = json_decode($data, true);
            } else {
                $decodedData = $data;
            }

            return $decodedData['plan_code'] ?? null;
        })->filter();

        DB::table('health_plan_coverage')->whereIn('plan_id', function ($query) use ($planCodes) {
            $query->select('id')->from('health_plan')->whereIn('code', $planCodes);
        })->delete();

        info('Coverage Record Created Start');
        RateCoverageProcess::where('rate_coverage_id', $uploadCoverages->id)
            ->chunk(500, function ($coverages) {

                $insertData = [];
                foreach ($coverages as $coverage) {
                    $data = is_string($coverage->data) ? json_decode($coverage->data, true) : $coverage->data;

                    if (empty($data['plan_code'])) {
                        continue;
                    }

                    $insertData[] = [
                        'code' => $data['code'] ?? '',
                        'text' => $data['text'] ?? '',
                        'description' => $data['description'] ?? '',
                        'value' => $data['value'] ?? '',
                        'type' => $data['type'] ?? '',
                        'is_northern' => $data['is_northern'] ?? '',
                        'plan_id' => DB::table('health_plan')->where('code', $data['plan_code'])->value('id'),
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($insertData)) {
                    DB::table('health_plan_coverage')->insert($insertData);
                }
            });
        $uploadCoverages->update(['status' => ProcessStatusCode::COMPLETED]);
    }

    public function getUploadCoverages()
    {
        $coverages = RateCoverageUpload::select(
            'rate_coverage_uploads.file_name as fileName',
            'rate_coverage_uploads.status as status',
            'rate_coverage_uploads.total_records as totalRecords',
            DB::raw('IF(COUNT(rate_coverage_processes.id) = 0, 0, SUM(CASE WHEN rate_coverage_processes.validation_errors IS NULL THEN 1 ELSE 0 END)) as good'),
            DB::raw('IF(COUNT(rate_coverage_processes.id) = 0, 0, SUM(CASE WHEN rate_coverage_processes.validation_errors IS NOT NULL THEN 1 ELSE 0 END)) as cannotUpload'),
            'rate_coverage_uploads.id as upload_id',
            'rate_coverage_uploads.created_at as created_at',
            'rate_coverage_uploads.updated_at as updated_at',
            'rate_coverage_processes.type as type',
        )
            ->where('rate_coverage_uploads.type', '=', RateCoverageEnum::COVERAGES)
            ->leftJoin('rate_coverage_processes', 'rate_coverage_processes.rate_coverage_id', '=', 'rate_coverage_uploads.id')
            ->groupBy('rate_coverage_uploads.id')
            ->orderBy('rate_coverage_uploads.created_at', 'desc')
            ->simplePaginate(10)
            ->withQueryString();

        return $coverages;
    }

    public function rateUploadCreate($data)
    {
        $uploadedFile = $this->uploadFile();

        $uploadRate = $this->createRate($uploadedFile);
        UploadRatesJob::dispatch($uploadRate);
    }

    public function createRate($uploadedFile)
    {
        $uploadLeadData = [
            'file_name' => $uploadedFile['file_name'],
            'file_path' => $uploadedFile['azure_file_path'],
            'status' => ProcessStatusCode::UPLOADED,
            'good' => 0,
            'cannot_upload' => 0,
            'type' => RateCoverageEnum::RATES,
        ];
        info('Rate Record Created In DB');

        return RateCoverageUpload::create($uploadLeadData);
    }

    public function processUploadRate(RateCoverageUpload $uploadRate)
    {
        $logPrefix = 'UAC FN: processUploadCreate CoverageId: '.$uploadRate->id.' FileName: '.$uploadRate->file_name;

        try {
            // Set status to IN_PROGRESS
            $uploadRate->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $uploadRate = DB::transaction(function () use ($uploadRate) {
                // Start file import
                $uploadRecord = new RatesImport($uploadRate);
                $uploadRecord->import($uploadRate->file_path, 'azureIM');

                $rateCoverageProcesses = RateCoverageProcess::where('rate_coverage_id', $uploadRate->id)->get();
                $validDataCount = 0;
                $failedDataCount = 0;

                foreach ($rateCoverageProcesses as $process) {
                    $data = $process->data;

                    if (empty($data['eligibility_code']) || empty($data['plan_code']) || empty($data['copayment_code'])) {
                        $failedDataCount++;

                        continue;
                    } else {
                        $validDataCount++;
                    }
                }
                $uploadRate->update([
                    'cannot_upload' => $failedDataCount,
                    'good' => $validDataCount,
                    'total_records' => $validDataCount + $failedDataCount,
                ]);

                return $uploadRate;
            });
            if ($uploadRate) {
                $this->createRateData($uploadRate);
            }

            info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            // Update the status to FAILED in case of an error
            $uploadRate->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.' Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

    public function createRateData($uploadRate)
    {
        // Delete existing records
        $planCodes = RateCoverageProcess::where('rate_coverage_id', $uploadRate->id)->pluck('data')->map(function ($data) {
            if (is_string($data)) {
                $decodedData = json_decode($data, true);
            } else {
                $decodedData = $data;
            }

            return $decodedData['plan_code'] ?? null;
        })->filter();

        DB::table('health_rates')->whereIn('health_plan_id', function ($query) use ($planCodes) {
            $query->select('id')->from('health_plan')->whereIn('code', $planCodes);
        })->delete();

        info('Rate Record Created Start');
        RateCoverageProcess::where('rate_coverage_id', $uploadRate->id)
            ->chunk(500, function ($coverages) {

                $insertData = [];
                foreach ($coverages as $coverage) {
                    $data = is_string($coverage->data) ? json_decode($coverage->data, true) : $coverage->data;

                    if (empty($data['eligibility_code']) || empty($data['plan_code']) || empty($data['copayment_code'])) {
                        continue;
                    }

                    $insertData[] = [
                        'is_northern' => $data['is_northern'] ?? '',
                        'min_age' => $data['min_age'] ?? '',
                        'max_age' => $data['max_age'] ?? '',
                        'gender' => $data['gender'] ?? '',
                        'premium' => $data['premium'] ?? '',
                        'health_rating_eligibility_id' => DB::table('health_rating_eligibilities')->where('code', $data['eligibility_code'])->value('id'),
                        'health_plan_id' => DB::table('health_plan')->where('code', $data['plan_code'])->value('id'),
                        'health_plan_co_payment_id' => DB::table('health_plan_co_payments')->where('code', $data['copayment_code'])->value('id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($insertData)) {
                    DB::table('health_rates')->insert($insertData);
                }
            });
        $uploadRate->update(['status' => ProcessStatusCode::COMPLETED]);
    }

    public function getUploadRates()
    {
        $rates = RateCoverageUpload::select(
            'rate_coverage_uploads.file_name as fileName',
            'rate_coverage_uploads.status as status',
            'rate_coverage_uploads.total_records as totalRecords',
            DB::raw('IF(COUNT(rate_coverage_processes.id) = 0, 0, SUM(CASE WHEN rate_coverage_processes.validation_errors IS NULL THEN 1 ELSE 0 END)) as good'),
            DB::raw('IF(COUNT(rate_coverage_processes.id) = 0, 0, SUM(CASE WHEN rate_coverage_processes.validation_errors IS NOT NULL THEN 1 ELSE 0 END)) as cannotUpload'),
            'rate_coverage_uploads.id as upload_id',
            'rate_coverage_uploads.created_at as created_at',
            'rate_coverage_uploads.updated_at as updated_at',
            'rate_coverage_processes.type as type',
        )
            ->where('rate_coverage_uploads.type', '=', RateCoverageEnum::RATES)
            ->leftJoin('rate_coverage_processes', 'rate_coverage_processes.rate_coverage_id', '=', 'rate_coverage_uploads.id')
            ->groupBy('rate_coverage_uploads.id')
            ->orderBy('rate_coverage_uploads.created_at', 'desc')
            ->simplePaginate(10)
            ->withQueryString();

        return $rates;
    }

    public function getBadRecords($id)
    {
        return RateCoverageProcess::where('rate_coverage_id', $id)
            ->whereNotNull('validation_errors')
            ->get(['data', 'validation_errors']);
    }

}
