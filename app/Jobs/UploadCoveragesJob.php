<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class UploadCoveragesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;

    /**
     * Create a new job instance.
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $data = Excel::toArray(null, Storage::path($this->file));
            $sheet = $data[0];
            $headers = $sheet[0];
            $headerMap = [];

            foreach ($headers as $index => $header) {
                if (is_string($header) && ! empty($header)) {
                    $headerMap[$header] = $index;
                }
            }

            $requiredHeaders = ['plan_code'];
            foreach ($requiredHeaders as $requiredHeader) {
                if (! isset($headerMap[$requiredHeader])) {
                    return;
                }
            }

            $chunkSize = 500;
            $coverages = [];
            $planCodesToDelete = [];

            foreach (array_chunk($sheet, $chunkSize, true) as $chunkIndex => $chunk) {
                foreach ($chunk as $index => $row) {
                    if ($index === 0 && $chunkIndex === 0) {
                        continue;
                    }

                    if (! empty($row[$headerMap['plan_code']])) {
                        $planCode = $row[$headerMap['plan_code']];
                        $planId = DB::table('health_plan')->where('code', $planCode)->value('id');

                        if ($planId) {
                            $planCodesToDelete[] = $planId;
                            $coverages[] = [
                                'code' => $row[$headerMap['Code']],
                                'text' => $row[$headerMap['text']],
                                'description' => $row[$headerMap['description']],
                                'value' => $row[$headerMap['value']],
                                'type' => $row[$headerMap['type']],
                                'is_northern' => $row[$headerMap['is_northern']],
                                'plan_id' => $planId,
                                'is_active' => 1,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ];
                        }
                    }
                }

                info('COVERAGES: '.print_r($coverages, true));
                info('RATES '.print_r($planCodesToDelete, true));

                if (! empty($planCodesToDelete)) {
                    // Delete existing coverages for the plan codes
                    DB::table('health_plan_coverage')->whereIn('plan_id', $planCodesToDelete)->delete();
                }

                if (! empty($coverages)) {
                    // Insert new coverages
                    DB::table('health_plan_coverage')->insert($coverages);
                }

                $coverages = [];
                $planCodesToDelete = [];
            }

        } finally {
            Storage::delete($this->file);
        }
    }
}
