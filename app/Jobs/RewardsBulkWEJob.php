<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Config;
use Illuminate\Http\Request;
use DB;

class RewardsBulkWEJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $dateTo;
    protected $dateFrom;
    protected $request;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $dateTo, $dateFrom)
    {
        $this->dateTo = $dateTo;
        $this->dateFrom = $dateFrom;
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
            $from = date($this->dateFrom);
            $to = date($this->dateTo);
            Log::info('Date From ' . $from . '| Date To ' . $to);
            $customers = Customer::whereBetween('created_at', [$from, $to])
                ->where('has_reward_access', 1)
                ->where('is_we_sent', 0)
                ->get();
            Log::info($customers->count() . ' Customers fetched based on criteria');
            foreach ($customers as $customer) {
                Log::info('Going to send email for customer having ' . $customer->email . ' , ' . $customer->id);
                $this->request->to = $customer->email;
                $params = ["customerName" => $customer->first_name . ' ' . $customer->last_name];
                $this->request->subject = 'Welcome to myAlfred by InsuranceMarket.ae';
                $this->request->templateName = 'customerWelcome';
                $this->request->templateParams = $params;
                dispatch(new RewardsWEJob(json_encode($this->request), $customer->id));
                Log::info('Email Job Dispatched for customer having ' . $customer->email . ' , ' . $customer->id);
            }
        } catch (\Exception $ex) {
            return $ex;
        } finally {
            DB::disconnect('mysql');
        }
    }
}
