<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RatesCoveragesUploadController extends Controller
{
    public function uploadCoverages()
    {
        return inertia('RatesCoverages/Health/Coverages');
    }

    public function coveragesUploadCreate(UploadRateCoverageRequest $request)
    {
        $file = $request->file('file_name');
        $data = Excel::toArray(null, $file);
        $coverages = [];

        $sheet = $data[0];
        $headers = $sheet[0];
        $headerMap = [];

        foreach ($headers as $index => $header) {
            if (is_string($header) && ! empty($header)) {
                $headerMap[$header] = $index;
            }
        }

        // Required headers
        // ADD Validation on Headers if Required 'Code', 'text', 'description', 'value', 'type',
        $requiredHeaders = ['plan_code'];

        foreach ($requiredHeaders as $requiredHeader) {
            if (! isset($headerMap[$requiredHeader])) {
                return response()->json(['error' => "Missing required header: $requiredHeader"], 422);
            }
        }

        foreach ($sheet as $index => $row) {
            if ($index === 0) {
                continue;
            }

            if (! empty($row[$headerMap['plan_code']])) {
                $planCode = $row[$headerMap['plan_code']];
                $planId = DB::table('health_plan')->where('code', $planCode)->value('id');

                if ($planId) {
                    foreach ($requiredHeaders as $requiredHeader) {
                        if (empty($row[$headerMap[$requiredHeader]]) && $requiredHeader !== 'plan_code') {
                            return response()->json(['error' => "Row $index is missing required field: $requiredHeader"], 422);
                        }
                    }

                    $coverages[] = [
                        'code' => $row[$headerMap['Code']],
                        'text' => $row[$headerMap['text']],
                        'description' => $row[$headerMap['description']],
                        'value' => $row[$headerMap['value']],
                        'type' => $row[$headerMap['type']],
                        'is_northern' => $row[$headerMap['is_northern']],
                        'plan_id' => $planId,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        $planCodesToDelete = array_column($coverages, 'plan_id');
        if (! empty($planCodesToDelete)) {
            DB::table('health_plan_coverage')->whereIn('plan_id', $planCodesToDelete)->delete();
        }

        if (! empty($coverages)) {
            DB::table('health_plan_coverage')->insert($coverages);
        }

        return response()->json(['message' => 'Coverages uploaded successfully.']);
    }

    public function uploadRates()
    {
        return inertia('RatesCoverages/Health/Rates');
    }

    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        // Get the uploaded file
        $file = $request->file('file_name');
        $data = Excel::toArray(null, $file);

        $rates = [];

        $sheet = $data[0];
        $headers = $sheet[0];
        $headerMap = [];

        foreach ($headers as $index => $header) {
            if (is_string($header) && ! empty($header)) {
                $headerMap[$header] = $index;
            }
        }

        // Required headers
        // ADD Validation on Headers if Required 'Code', 'text', 'description', 'value', 'type',
        $requiredHeaders = ['eligibility_code', 'copayment_code', 'plan_code'];

        foreach ($requiredHeaders as $requiredHeader) {
            if (! isset($headerMap[$requiredHeader])) {
                return response()->json(['error' => "Missing required header: $requiredHeader"], 422);
            }
        }

        foreach ($sheet as $index => $row) {
            if ($index === 0) {
                continue;
            }

            if (isset($headerMap['plan_code']) && ! empty($row[$headerMap['plan_code']])) {
                $planCode = $row[$headerMap['plan_code']];
                $planId = DB::table('health_plan')->where('code', $planCode)->value('id');
                if ($planId) {
                    $rates[] = [
                        'health_rating_eligibility_id' => DB::table('health_rating_eligibilities')->where('code', $row[$headerMap['eligibility_code']])->value('id'),
                        'health_plan_id' => $planId,
                        'health_plan_co_payment_id' => DB::table('health_plan_co_payments')->where('code', $row[$headerMap['copayment_code']])->value('id'),
                        'is_northern' => $row[$headerMap['is_northern']],
                        'min_age' => $row[$headerMap['min_age']],
                        'max_age' => $row[$headerMap['max_age']],
                        'gender' => $row[$headerMap['gender']],
                        'premium' => str_replace(',', '', $row[$headerMap['premium']]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        $planCodesToDelete = array_column($rates, 'health_plan_id');
        if (! empty($planCodesToDelete)) {
            DB::table('health_rates')->whereIn('health_plan_id', $planCodesToDelete)->delete();
        }

        if (! empty($rates)) {
            DB::table('health_rates')->insert($rates);
        }

        return response()->json(['message' => 'Health rates updated successfully.']);
    }

}
