<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Enums\RateCoverageEnum;
use App\Imports\CoveragesImport;
use App\Imports\RatesImport;
use App\Jobs\UploadCoveragesJob;
use App\Jobs\UploadRatesJob;
use App\Models\RateCoveragesProcess;
use App\Models\RatesCoveragesUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatesCoveragesUploadService
{
    public function uploadFile()
    {
        $path = 'ratings/health';
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $azureFileName = get_guid().'_'.$fileName;

        $azureFilePath = request()->file('file_name')->storeAs($path, $azureFileName, 'azureIM');

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

        return RatesCoveragesUpload::create($uploadLeadData);
    }

    public function coveragesUploadCreate($data)
    {
        $uploadedFile = $this->uploadFile();

        $uploadCoverages = $this->createCoverages($uploadedFile);

        UploadCoveragesJob::dispatch($uploadCoverages);

        return true;
    }

    public function processUploadCoverages(RatesCoveragesUpload $uploadCoverages)
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

                $rateCoveragesProcesses = RateCoveragesProcess::where('rate_coverage_id', $uploadCoverages->id)->get();

                $validDataCount = 0;
                $failedDataCount = 0;

                foreach ($rateCoveragesProcesses as $process) {
                    $data = $process->data;

                    if (empty($data['code']) || empty($data['text']) || empty($data['description']) || empty($data['value']) || empty($data['type']) || empty($data['is_northern']) || empty($data['plan_code'])) {
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
        RateCoveragesProcess::where('rate_coverage_id', $uploadCoverages->id)
            ->chunk(500, function ($coverages) {
                $planCodes = $coverages->pluck('data')->map(function ($data) {
                    if (is_string($data)) {
                        $decodedData = json_decode($data, true);
                    } else {
                        $decodedData = $data;
                    }

                    return $decodedData['plan_code'] ?? null;
                })->filter();

                if ($planCodes->isNotEmpty()) {
                    DB::table('health_plan_coverage')->whereIn('plan_id', function ($query) use ($planCodes) {
                        $query->select('id')->from('health_plan')->whereIn('code', $planCodes);
                    })->delete();
                }

                $insertData = [];
                foreach ($coverages as $coverage) {
                    $data = is_string($coverage->data) ? json_decode($coverage->data, true) : $coverage->data;

                    if (empty($data['code']) || empty($data['text']) || empty($data['description']) || empty($data['value']) || empty($data['type']) || empty($data['plan_code'])) {
                        continue;
                    }

                    $insertData[] = [
                        'code' => $data['code'] ?? '',
                        'text' => $data['text'] ?? '',
                        'description' => $data['description'] ?? '',
                        'value' => $data['value'] ?? '',
                        'type' => $data['type'] ?? '',
                        'is_northern' => 1,
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
        $coverages = RateCoveragesProcess::select(
            'rate_coverage_uploads.file_name as fileName',
            'rate_coverage_uploads.status as status',
            'rate_coverage_uploads.total_records as totalRecords',
            DB::raw('SUM(CASE WHEN rate_coverage_processes.validation_errors IS NULL THEN 1 ELSE 0 END) as good'),
            DB::raw('SUM(CASE WHEN rate_coverage_processes.validation_errors IS NOT NULL THEN 1 ELSE 0 END) as cannotUpload'),
            'rate_coverage_uploads.id as upload_id',
            'rate_coverage_processes.type as type',
            DB::raw('GROUP_CONCAT(rate_coverage_processes.validation_errors SEPARATOR \', \') as error')
        )
            ->where('rate_coverage_uploads.type', '=', RateCoverageEnum::COVERAGES)
            ->leftJoin('rate_coverage_uploads', 'rate_coverage_processes.rate_coverage_id', '=', 'rate_coverage_uploads.id')
            ->groupBy('rate_coverage_uploads.id')
            ->simplePaginate(10)
            ->withQueryString();

        return $coverages;
    }

    public function rateUploadCreate($data)
    {
        $uploadedFile = $this->uploadFile();

        $uploadRate = $this->createRate($uploadedFile);

        UploadRatesJob::dispatch($uploadRate);

        return true;
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

        return RatesCoveragesUpload::create($uploadLeadData);
    }

    public function processUploadRate(RatesCoveragesUpload $uploadRate)
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

                $rateCoveragesProcesses = RateCoveragesProcess::where('rate_coverage_id', $uploadRate->id)->get();
                $validDataCount = 0;
                $failedDataCount = 0;

                foreach ($rateCoveragesProcesses as $process) {
                    $data = $process->data;

                    if (empty($data['is_northern']) || empty($data['min_age']) || empty($data['max_age']) || empty($data['gender']) || empty($data['premium']) || empty($data['eligibility_code']) || empty($data['plan_code']) || empty($data['copayment_code'])) {
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
        RateCoveragesProcess::where('rate_coverage_id', $uploadRate->id)
            ->chunk(500, function ($coverages) {
                $planCodes = $coverages->pluck('data')->map(function ($data) {
                    if (is_string($data)) {
                        $decodedData = json_decode($data, true);
                    } else {
                        $decodedData = $data;
                    }

                    return $decodedData['plan_code'] ?? null;
                })->filter();

                if ($planCodes->isNotEmpty()) {
                    DB::table('health_rates')->whereIn('health_plan_id', function ($query) use ($planCodes) {
                        $query->select('id')->from('health_plan')->whereIn('code', $planCodes);
                    })->delete();
                }

                $insertData = [];
                foreach ($coverages as $coverage) {
                    $data = is_string($coverage->data) ? json_decode($coverage->data, true) : $coverage->data;

                    if (empty($data['is_northern']) || empty($data['min_age']) || empty($data['max_age']) || empty($data['gender']) || empty($data['premium']) || empty($data['eligibility_code']) || empty($data['plan_code']) || empty($data['copayment_code'])) {
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
        $rates = RateCoveragesProcess::select(
            'rate_coverage_uploads.file_name as fileName',
            'rate_coverage_uploads.status as status',
            'rate_coverage_uploads.total_records as totalRecords',
            DB::raw('SUM(CASE WHEN rate_coverage_processes.validation_errors IS NULL THEN 1 ELSE 0 END) as good'),
            DB::raw('SUM(CASE WHEN rate_coverage_processes.validation_errors IS NOT NULL THEN 1 ELSE 0 END) as cannotUpload'),
            'rate_coverage_uploads.id as upload_id',
            'rate_coverage_processes.type as type',
            DB::raw('GROUP_CONCAT(rate_coverage_processes.validation_errors SEPARATOR \', \') as error')
        )
            ->where('rate_coverage_uploads.type', '=', RateCoverageEnum::RATES)
            ->leftJoin('rate_coverage_uploads', 'rate_coverage_processes.rate_coverage_id', '=', 'rate_coverage_uploads.id')
            ->groupBy('rate_coverage_uploads.id')
            ->simplePaginate(10)
            ->withQueryString();

        return $rates;
    }

}
