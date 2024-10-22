<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Imports\CoveragesImport;
use App\Imports\UploadAndCreateImport;
use App\Jobs\UploadCoveragesJob;
use App\Models\RatesCoveragesUpload;
use App\Models\RenewalsUploadLeads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatesCoveragesUploadService
{
    public function uploadFile($isTravel = false)
    {
        $path = 'renewals'; //Changing to exact path
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

    public function processUploadCreate(RatesCoveragesUpload $uploadCoverages)
    {
        $logPrefix = 'UAC FN: processUploadCreate CoverageId: '.$uploadCoverages->id.' FileName: '.$uploadCoverages->file_name;

        try {
            $uploadCoverages->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            info($logPrefix.' In Progress Now');

            $uploadCoverages = DB::transaction(function () use ($uploadCoverages) {
                //start file import
                $renewalsUpload = new CoveragesImport($uploadCoverages);
                $renewalsUpload->import($uploadCoverages->file_path, 'azureIM');

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

            info($logPrefix.' excel data stored in DB');

            $validationResult = $this->uploadedLeadsValidation($renewalsUploadLead);
            if ($validationResult) {
                $this->createQuotes($renewalsUploadLead);
            }

            info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            $uploadCoverages->update(['status' => ProcessStatusCode::FAILED]);
            Log::error($logPrefix.'Process Failed. Error: '.$exception->getMessage());

            return false;
        }
    }

}
