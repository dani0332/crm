<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\RateCoverageEnum;
use App\Imports\CoveragesImport;
use App\Imports\RatesImport;
use App\Jobs\UploadCoveragesJob;
use App\Models\HealthRateControl;
use App\Models\RateCoverageProcess;
use App\Models\RateCoverageUpload;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RateCoverageUploadService
{
    public function __construct(
        private HealthPlanService $healthPlanService,
        private CohortMappingService $cohortMappingService,
        private HealthPlanCoPaymentService $healthPlanCoPaymentService,
        private HealthRateControlService $healthRateControlService
    ) {}

    public function uploadFile()
    {
        $path = 'ratings/health';
        // Getting original file name
        $fileName = request()->file('file_name')->getClientOriginalName();

        // Generating name for file for azure usage
        $datetime = date('Y-m-d_H-i-s');
        $azureFileName = $datetime.'_'.$fileName;

        $azureFilePath = request()->file('file_name')->storeAs($path, $azureFileName, 'azureIM');
        LoggerService::info('File saved in Azure storage', [
            'file_path' => $azureFilePath,
        ]);

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
        LoggerService::info('Coverage record created in database');

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

            LoggerService::info($logPrefix.' In Progress Now');

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

            LoggerService::info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            // Update the status to FAILED in case of an error
            $uploadCoverages->update(['status' => ProcessStatusCode::FAILED]);
            LoggerService::error($logPrefix.' Process Failed. Error: '.$exception->getMessage());

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

        LoggerService::info('Coverage Record Created Start');
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
                        'emirate_type' => $data['emirate_type'] ?? null,
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
        $excelRecords = [];
        if ($data && isset($data['file_name']) && file_exists($data['file_name'])) {
            $excelRecords = Excel::toArray([], $data['file_name']);

            // Assuming first sheet, and first row is header
            $rows = $excelRecords[0] ?? [];
            $rowCount = count($rows);
            if ($rowCount > 1) {
                // normalize header case
                $headers = array_map('strtolower', $rows[0]);
                $allCohorts = $this->cohortMappingService->getAllCohorts();
                $allCoPayments = $this->healthPlanCoPaymentService->getAllCoPayments();

                // Iterate through rows to get values
                for ($i = 1; $i < $rowCount; $i++) {
                    $rowAssoc = array_combine($headers, $rows[$i]);
                    $planCode = $rowAssoc['plan_code'] ?? null;
                    $minAge = $rowAssoc['min_age'] ?? null;
                    $maxAge = $rowAssoc['max_age'] ?? null;
                    $premium = $rowAssoc['premium'] ?? null;
                    $eligibilityCode = $rowAssoc['eligibility_code'] ?? null;
                    $copaymentCode = $rowAssoc['copayment_code'] ?? null;

                    // Throw error if any required value is empty
                    if (
                        empty($planCode) ||
                        empty($minAge) ||
                        empty($maxAge) ||
                        empty($premium) ||
                        empty($eligibilityCode) ||
                        empty($copaymentCode)
                    ) {
                        throw new \Exception("All fields (plan_code, min_age, max_age, premium, marital_status, eligibility_code, copayment_code) are required. Please check row {$i}");
                    }

                    // Validate plan code is the same as the first plan code
                    // To get previous row's record as an associative array:
                    if ($i > 1) {
                        $prevRowAssoc = array_combine($headers, $rows[$i - 1]);
                        if ($planCode !== $prevRowAssoc['plan_code']) {
                            throw new \Exception('All plan codes must be the same.');
                        }
                    }

                    $plan = $this->healthPlanService->getPlanByCode($planCode);
                    if (! $plan) {
                        throw new \Exception('Plan not found.');
                    }

                    // Validate min age as integers
                    if (! ctype_digit(strval($minAge))) {
                        throw new \Exception('All min ages values must be integers.');
                    }

                    // Validate max age as integers
                    if (! ctype_digit(strval($maxAge))) {
                        throw new \Exception('All max ages values must be integers.');
                    }

                    // Validate premium as integers
                    if (! ctype_digit(strval($premium))) {
                        throw new \Exception('All premiums values must be integers.');
                    }

                    // Validate gender based on plan gender enabled
                    if ($plan->gender_enabled) {
                        $gender = $rowAssoc['gender'] ?? null;
                        if (empty($gender)) {
                            throw new \Exception('Gender is required when plan gender is enabled.');
                        }

                        $allowedGenders = ['male', 'female'];
                        if (! in_array(strtolower($gender), $allowedGenders, true)) {
                            throw new \Exception('Gender value must be either "male" or "female".');
                        }
                    }

                    // Validate cohort based on plan cohort enabled
                    if ($plan->cohort_enabled) {
                        $gender = $rowAssoc['gender'] ?? null;
                        if (empty($gender)) {
                            throw new \Exception('Gender is required when plan gender is enabled.');
                        }

                        if (! in_array($cohort, $allCohorts, true)) {
                            throw new \Exception('Invalid cohort value.');
                        }
                    }

                    // Validate marital status based on plan marital status enabled
                    if ($plan->marital_status_enabled && ! $plan->gender_enabled) {
                        throw new \Exception('Gender must be enabled when marital status is enabled.');
                    }

                    if ($plan->marital_status_enabled) {
                        $maritalStatus = $rowAssoc['marital_status'] ?? null;
                        if (empty($maritalStatus)) {
                            throw new \Exception('Marital status is required when marital status is enabled.');
                        }

                        $allowedMaritalStatuses = ['single', 'married'];
                        if (! in_array(strtolower($maritalStatus), $allowedMaritalStatuses, true)) {
                            throw new \Exception('Marital status value must be either "single" or "married".');
                        }
                    }

                    // Validate if copayment code is in the list of all co payments
                    if ($copaymentCode && ! in_array($copaymentCode, $allCoPayments, true)) {
                        throw new \Exception('Invalid copayment code value.');
                    }
                }
            } else {
                throw new \Exception('No data found in the file.');
            }
        }
        // Upload file (health rate control)
        $this->uploadHealthRateControl($uploadedFile['file_name'], $data['effective_from'], $data['effective_to'], $plan->id, $rowCount - 1);
    }

    private function uploadHealthRateControl(string $fileName, $effectiveFrom, $effectiveTo, int $planId, int $totalRecords)
    {
        // Check if any draft version exists against plan
        $draftVersion = $this->healthRateControlService->getByPlanIdAndStatus($planId, HealthPlanRateSheetStatusEnum::DRAFT);

        if ($draftVersion) {
            throw new \Exception('Draft version already exists for this plan.');
        }

        // Derive plan versiob
        $rateVersion = $this->deriveRateVersion($planId);

        $uploadLeadData = [
            'file_name' => $fileName,
            'health_plan_id' => $planId,
            'version' => $rateVersion,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'total_records' => $totalRecords,
            'created_by' => auth()->user()->id,
            'status' => HealthPlanRateSheetStatusEnum::DRAFT,
        ];

        HealthRateControl::create($uploadLeadData);
    }

    private function deriveRateVersion(int $planId): float
    {
        // Get Active plan version (if exists)
        $plan = HealthRateControl::where('health_plan_id', $planId)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE)
            ->first();

        // If active version exists, return next minor version for draft
        if ($plan) {
            return $plan->version + 0.1;
        }

        // If no active version exists, return 1.0
        return 1.0;
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
        LoggerService::info('Rate record created in database');

        return RateCoverageUpload::create($uploadLeadData);
    }

    public function processUploadRate(RateCoverageUpload $uploadRate)
    {
        $logPrefix = 'UAC FN: processUploadCreate CoverageId: '.$uploadRate->id.' FileName: '.$uploadRate->file_name;

        try {
            // Set status to IN_PROGRESS
            $uploadRate->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            LoggerService::info($logPrefix.' In Progress Now');

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

            LoggerService::info($logPrefix.' validation and creation is completed');

            return true;
        } catch (\Exception $exception) {
            // Update the status to FAILED in case of an error
            $uploadRate->update(['status' => ProcessStatusCode::FAILED]);
            LoggerService::error($logPrefix.' Process Failed. Error: '.$exception->getMessage());

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

        LoggerService::info('Rate Record Created Start');
        RateCoverageProcess::where('rate_coverage_id', $uploadRate->id)
            ->chunk(500, function ($coverages) {

                $insertData = [];
                foreach ($coverages as $coverage) {
                    $data = is_string($coverage->data) ? json_decode($coverage->data, true) : $coverage->data;

                    if (empty($data['eligibility_code']) || empty($data['plan_code']) || empty($data['copayment_code'])) {
                        continue;
                    }

                    $insertData[] = [
                        'emirate_type' => $data['emirate_type'] ?? null,
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
        $rates = HealthRateControl::with('user', 'healthPlan')->orderByDesc('id')->simplePaginate(10)->withQueryString();

        return $rates;
    }

    public function getBadRecords($id)
    {
        return RateCoverageProcess::where('rate_coverage_id', $id)
            ->whereNotNull('validation_errors')
            ->get(['data', 'validation_errors']);
    }

}
