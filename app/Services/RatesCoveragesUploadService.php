<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Imports\CoveragesImport;
use App\Jobs\UploadCoveragesJob;
use App\Models\RateCoveragesProcess;
use App\Models\RatesCoveragesUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatesCoveragesUploadService
{
    public function uploadFile()
    {
        $path = 'documents/'; //Changing to exact path
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $azureFileName = get_guid().'_'.$fileName;

        $azureFilePath = request()->file('file_name')->storeAs($path, $azureFileName, 'local');

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
        ];

        return RatesCoveragesUpload::create($uploadLeadData);
    }

    public function coveragesUploadCreate($data)
    {
        //upload renewal file to azure
        $uploadedFile = $this->uploadFile();

        $uploadCoverages = $this->createCoverages($uploadedFile);

        UploadCoveragesJob::dispatch($uploadCoverages);

        return true;
    }

    public function processUploadCoverages(RatesCoveragesUpload $uploadCoverages)
    {
        $logPrefix = 'UAC FN: processUploadCreate CoverageId: '.$uploadCoverages->id.' FileName: '.$uploadCoverages->file_name;

        try {
            $uploadCoverages->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $uploadCoverages = DB::transaction(function () use ($uploadCoverages) {
                //start file import
                $renewalsUpload = new CoveragesImport($uploadCoverages);
                $renewalsUpload->import($uploadCoverages->file_path, 'local');

                //update counts
                $validRows = $renewalsUpload->getValidCount();
                $failedRows = $renewalsUpload->getFailedCount();

                $uploadCoverages->update([
                    'cannot_upload' => $failedRows,
                    'good' => 0,
                    'total_records' => ($validRows + $failedRows),
                ]);

                return $uploadCoverages;
            });

            if ($uploadCoverages) {
                $this->createCoveragesData($uploadCoverages);
            }

            info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            $uploadCoverages->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.'Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

    public function createCoveragesData($uploadCoverages)
    {
        RateCoveragesProcess::where('rate_coverage_id', $uploadCoverages->id)
            ->chunk(100, function ($coverages) use ($uploadCoverages) {
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

                    $insertData[] = [
                        'code' => $data['code'] ?? '',
                        'text' => $data['text'] ?? '',
                        'text_ar' => null,
                        'description' => $data['description'] ?? '',
                        'description_ar' => null,
                        'value' => $data['value'] ?? '',
                        'value_ar' => null,
                        'type' => $data['type'] ?? '',
                        'is_northern' => null,
                        'plan_id' => DB::table('health_plan')->where('code', $data['plan_code'])->value('id'),
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($insertData)) {
                    DB::table('health_plan_coverage')->insert($insertData);
                    $uploadCoverages->good += count($insertData);
                    $uploadCoverages->save();
                }
            });
    }

}
