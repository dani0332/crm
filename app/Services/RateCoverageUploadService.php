<?php

namespace App\Services;

use App\Enums\EmirateTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\RateCoverageEnum;
use App\Imports\CoveragesImport;
use App\Imports\RatesImport;
use App\Jobs\UploadCoveragesJob;
use App\Models\HealthRate;
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

    private function removeEmptyRows(array $rows): array
    {
        $filteredRows = [];
        foreach ($rows as $index => $row) {
            // Consider non-empty if at least one cell is not null/empty string/empty after trim
            $hasValue = false;
            foreach ($row as $cell) {
                if (! is_null($cell) && trim($cell) !== '') {
                    $hasValue = true;
                    break;
                }
            }
            // Always include header row (index 0), and any non-empty rows
            if ($index === 0 || $hasValue) {
                $filteredRows[] = $row;
            }
        }

        return $filteredRows;
    }

    public function rateUploadCreate($data)
    {
        $uploadedFile = $this->uploadFile();
        $seenCombinations = [];
        $excelRecords = [];

        if ($data && isset($data['file_name']) && file_exists($data['file_name'])) {
            $excelRecords = Excel::toArray([], $data['file_name']);

            // Assuming first sheet, and first row is header
            $rows = $excelRecords[0] ?? [];

            // Remove empty rows (skip header for now, so keep index 0)
            $rows = $this->removeEmptyRows($rows);
            $rowCount = count($rows);
            $plan = null;

            if ($rowCount > 1) {
                // normalize header case
                $headers = array_map('strtolower', $rows[0]);
                $allCohorts = $this->cohortMappingService->getAllCohorts();
                $allCoPayments = $this->healthPlanCoPaymentService->getAllCoPayments();

                // Get first plan code to validate from database
                $rowAssoc = array_combine($headers, $rows[1]);
                if (empty($rowAssoc['plan_code'])) {
                    throw new \Exception('Plan code is required.');
                }

                $plans = $this->healthPlanService->getPlanByCode($rowAssoc['plan_code']);
                if ($plans->isEmpty()) {
                    throw new \Exception('Plan not found.');
                }

                // Filter draft plan if exists
                $plan = $plans->firstWhere('status', HealthPlanRateSheetStatusEnum::DRAFT->value);
                // If draft does not exist, then take first plan (active)
                if (! $plan) {
                    $plan = $plans->first();
                }

                // Iterate through rows to get values
                for ($i = 1; $i < $rowCount; $i++) {
                    $rowAssoc = array_combine($headers, $rows[$i]);
                    $planCode = $rowAssoc['plan_code'] ?? null;
                    $minAge = $rowAssoc['min_age'] ?? null;
                    $maxAge = $rowAssoc['max_age'] ?? null;
                    $premium = $rowAssoc['premium'] ?? null;
                    $copaymentCode = $rowAssoc['copayment_code'] ?? null;
                    $emirateType = $rowAssoc['emirate_type'] ?? null;
                    $gender = $plan->gender_enabled ? $rowAssoc['gender'] ?? null : null;
                    $maritalStatus = $plan->marital_status_enabled
                        && ! empty($gender)
                        && strtolower($gender) == strtolower(GenderEnum::FEMALE->value) ? $rowAssoc['marital_status'] ?? null : null;
                    $cohort = $plan->cohort_enabled ? $rowAssoc['cohort'] ?? null : null;

                    // Throw error if any required value is empty
                    if (
                        empty($planCode) ||
                        ! isset($minAge) ||
                        ! isset($maxAge) ||
                        empty($premium) ||
                        empty($copaymentCode) ||
                        empty($emirateType)
                    ) {
                        throw new \Exception("All fields (plan_code, min_age, max_age, premium, copayment_code, emirate_type) are required. Please check row {$i}");
                    }

                    // Validate plan code is the same as the first plan code
                    // To get previous row's record as an associative array:
                    if ($i > 1) {
                        $prevRowAssoc = array_combine($headers, $rows[$i - 1]);
                        if ($planCode !== $prevRowAssoc['plan_code']) {
                            throw new \Exception('All plan codes must be the same.');
                        }
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
                    if (! preg_match('/^\d+(\.\d{1,2})?$/', strval($premium))) {

                        throw new \Exception('All premiums can be a decimal upto 2 digits.');
                    }

                    // Validate gender based on plan gender enabled
                    if ($plan->gender_enabled) {
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
                        if (empty($cohort)) {
                            throw new \Exception('Cohort is required when plan cohort is enabled.');
                        }

                        if (! in_array(strtoupper($cohort), $allCohorts, true)) {
                            throw new \Exception('Invalid cohort value.');
                        }
                    }

                    // Validate marital status based on plan marital status enabled
                    if ($plan->marital_status_enabled) {
                        // Plan gender must be enabled if marital status is enabled, other throw error
                        if (! $plan->gender_enabled) {
                            throw new \Exception('Gender must be enabled when marital status is enabled.');
                        }

                        if (empty($maritalStatus) && strtolower($gender) == strtolower(GenderEnum::FEMALE->value)) {
                            throw new \Exception('Marital status is required when gender is female and plan marital status is enabled.');
                        }

                        $allowedMaritalStatuses = ['single', 'married'];
                        if (! empty($maritalStatus) && ! in_array(strtolower($maritalStatus), $allowedMaritalStatuses, true)) {
                            throw new \Exception('Marital status value must be either "single" or "married".');
                        }
                    }

                    // Validate if copayment code is in the list of all co payments
                    if ($copaymentCode && ! in_array($copaymentCode, $allCoPayments, true)) {
                        throw new \Exception('Invalid copayment code value.');
                    }

                    // Validate emirate type
                    if (! in_array($emirateType, EmirateTypeEnum::labels(), true)) {
                        throw new \Exception('Invalid emirate type value.');
                    }

                    // Apply unique combination
                    $combinationKey = "{$copaymentCode}|{$gender}|{$maritalStatus}|{$cohort}";

                    // Initialize storage for combinations if not already
                    if (! isset($seenCombinations)) {
                        $seenCombinations = [];
                    }

                    // Check if this combinationKey has been seen before
                    if (isset($seenCombinations[$combinationKey])) {
                        // If combination detected, check if age ranges overlap
                        foreach ($seenCombinations[$combinationKey] as $seenAgeRange) {
                            // Check if min_age and max_age overlap
                            if (
                                ($minAge <= $seenAgeRange['max_age'] && $maxAge >= $seenAgeRange['min_age'])
                            ) {
                                throw new \Exception('Duplicate row detected at row '.($i + 1));
                            }
                        }
                        // If no overlap, add the new age range to the combination
                        $seenCombinations[$combinationKey][] = [
                            'min_age' => $minAge,
                            'max_age' => $maxAge,
                        ];
                    } else {
                        // First time this combination, add age range array
                        $seenCombinations[$combinationKey][] = [
                            'min_age' => $minAge,
                            'max_age' => $maxAge,
                        ];
                    }
                }

                // Upload file and rates in a transaction
                DB::transaction(function () use ($uploadedFile, $plan, $rows, $rowCount, $data) {
                    // Upload file (health rate control)
                    $result = $this->uploadHealthRateControl($uploadedFile['file_name'], $data['effective_from'], $data['effective_to'], $plan->id, $plan->code, $rowCount - 1);

                    // Upload rates
                    $this->uploadRates($plan->id, $result['health_rate_control_id'], $result['version'], $rows);
                });

            } else {
                throw new \Exception('No data found in the file.');
            }
        }
    }

    private function uploadHealthRateControl(
        string $fileName,
        $effectiveFrom,
        $effectiveTo,
        int $planId,
        $planCode,
        int $totalRecords): array
    {
        // Check if any draft/scheduled version exists against plan
        $draftVersion = $this->healthRateControlService->getByPlanIdAndStatus($planId, [HealthPlanRateSheetStatusEnum::DRAFT->value, HealthPlanRateSheetStatusEnum::SCHEDULED->value]);

        if ($draftVersion) {
            throw new \Exception("Upload rejected. A pending rate sheet already exists for plan {$planCode}. Please delete the existing Draft/Scheduled rate sheet before uploading a new one");
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

        $healthRateControl = HealthRateControl::create($uploadLeadData);

        return [
            'health_rate_control_id' => $healthRateControl->id,
            'version' => $rateVersion,
        ];
    }

    private function uploadRates(int $planId, int $healthRateControlId, float $version, array $data)
    {
        $plan = $this->healthPlanService->getPlanById($planId);
        $headers = array_map('strtolower', $data[0]);
        $codes = [];

        // Get all the payment codes first
        // To save databse query everytime in loop
        for ($i = 1; $i < count($data); $i++) {
            $rowAssoc = array_combine($headers, $data[$i]);

            $rows[] = $rowAssoc;
            $codes[] = $rowAssoc['copayment_code'];
        }

        $codes = array_unique($codes);
        $coPayments = $this->healthPlanCoPaymentService->getByCodes($codes);

        // Iterate again to create rates
        for ($i = 1; $i < count($data); $i++) {
            $rowAssoc = array_combine($headers, $data[$i]);
            $coPayment = $coPayments[$rowAssoc['copayment_code']];
            $emirateType = EmirateTypeEnum::fromText($rowAssoc['emirate_type']);

            // Check if we can add bulk insert outside loop
            HealthRate::create([
                'health_plan_id' => $planId,
                'health_rate_control_id' => $healthRateControlId,
                'version' => $version,
                'health_plan_co_payment_id' => $coPayment->id,
                'emirate_type' => $emirateType->value,
                'min_age' => $rowAssoc['min_age'],
                'max_age' => $rowAssoc['max_age'],
                'gender' => $plan->gender_enabled ? $rowAssoc['gender'] : null,
                'marital_status' => $plan->marital_status_enabled
                        && ! empty($rowAssoc['gender'])
                        && strtolower($rowAssoc['gender']) == strtolower(GenderEnum::FEMALE->value) ? $rowAssoc['marital_status'] : null,
                'cohort' => $plan->cohort_enabled ? $rowAssoc['cohort'] : null,
                'premium' => $rowAssoc['premium'],
                'status' => HealthPlanRateSheetStatusEnum::DRAFT,
                'is_active' => 0,
            ]);
        }
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
