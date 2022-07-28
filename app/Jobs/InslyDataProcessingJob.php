<?php

namespace App\Jobs;

use App\Models\InslyBatchLog;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InslyDataProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $request;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = json_decode($request);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Process InslyDataProcessingJob trigged');
            $arrayOfPolicyArray = array_chunk((array) $this->request->policies, 100);
            $count = 1;
            foreach ($arrayOfPolicyArray as $policyArray) {
                Log::info('Dispatched Job # '.$count.' for creating customer using policy');
                dispatch(new InslyCustomerCreationJob($policyArray));
                $count++;
            }
            Log::info('Fetching last batch record to update after process completion');
            $lastBatch = InslyBatchLog::orderBy('created_at', 'desc')->get()->first();
            $lastBatch->is_batch_completed = true;
            $lastBatch->save();

            return;
        } catch (Exception $ex) {
            return $ex;
        } finally {
            DB::disconnect('mysql');
        }
    }

    public static function GetWEEmailRequestObject($first_name, $last_name, $customer_email)
    {
        $params = ['customerName' => $first_name.' '.$last_name];
        $emailRequest = [];
        $emailRequest['to'] = $customer_email;
        $emailRequest['subject'] = 'Welcome to myAlfred by InsuranceMarket.ae';
        $emailRequest['templateName'] = 'customerWelcome';
        $emailRequest['templateParams'] = $params;

        return $emailRequest;
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        return $exception;
    }
}
