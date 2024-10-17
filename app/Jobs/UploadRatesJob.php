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

class UploadRatesJob implements ShouldQueue
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
            $data = Excel::toArray(null, $this->file);
            $sheet = $data[0];
            $headers = $sheet[0];
            $headerMap = [];

            foreach ($headers as $index => $header) {
                if (is_string($header) && ! empty($header)) {
                    $headerMap[$header] = $index;
                }
            }

            $requiredHeaders = ['eligibility_code', 'copayment_code', 'plan_code'];
            foreach ($requiredHeaders as $requiredHeader) {
                if (! isset($headerMap[$requiredHeader])) {
                    return;
                }
            }

            $chunkSize = 500;
            $rates = [];
            $planCodesToDelete = [];

            foreach (array_chunk($sheet, $chunkSize, true) as $chunkIndex => $chunk) {
                foreach ($chunk as $index => $row) {
                    if ($index === 0 && $chunkIndex === 0) {
                        continue;
                    }

                    if (isset($headerMap['plan_code']) && ! empty($row[$headerMap['plan_code']])) {
                        $planCode = $row[$headerMap['plan_code']];
                        $planId = DB::table('health_plan')->where('code', $planCode)->value('id');
                        if ($planId) {
                            $planCodesToDelete[] = $planId;
                            $rates[] = [
                                'health_rating_eligibility_id' => DB::table('health_rating_eligibilities')->where('code', $row[$headerMap['eligibility_code']])->value('id'),
                                'health_plan_id' => $planId,
                                'health_plan_co_payment_id' => DB::table('health_plan_co_payments')->where('code', $row[$headerMap['copayment_code']])->value('id'),
                                'is_northern' => $row[$headerMap['is_northern']],
                                'min_age' => $row[$headerMap['min_age']],
                                'max_age' => $row[$headerMap['max_age']],
                                'gender' => $row[$headerMap['gender']],
                                'premium' => str_replace(',', '', $row[$headerMap['premium']]),
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ];
                        }
                    }
                }

                if (! empty($planCodesToDelete)) {
                    DB::table('health_rates')->whereIn('health_plan_id', $planCodesToDelete)->delete();
                }

                if (! empty($rates)) {
                    DB::table('health_rates')->insert($rates);
                }

                $rates = [];
                $planCodesToDelete = [];
            }
        } finally {
            Storage::delete($this->file);
        }

    }
}
