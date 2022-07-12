<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\QuoteCustomer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;
use DB;

class CreateQuoteCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $dateFrom;
    public $dateTo;
    public $cdb_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dateFrom, $dateTo, $cdb_id)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->cdb_id = $cdb_id;
        Log::info('CreateQuoteCustomers Job Construct ---> Date From ' . $this->dateFrom . '| Date To ' . $this->dateTo . '| CDB ID ' . $this->cdb_id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $from = $this->dateFrom;
            $to = $this->dateTo;
            $cdbId = $this->cdb_id;
            Log::info('CreateQuoteCustomers ---> Date From ' . $from . '| Date To ' . $to . ' | CDB ID ' . $cdbId);
            $getAllCustomers = CustomerService::getAllCustomers($from, $to);
            Log::info($getAllCustomers->count() . ' Customers fetched based on criteria in the CreateQuoteCustomers job');
            foreach ($getAllCustomers as $customer) {
                Log::info('Saving quote customer having Customer Id-> ' . $customer->id . ' , CDB Id ->' . $cdbId);

                $customerExists = CreateQuoteCustomers::findQuoteCustomerById($customer->id);
                if ($customerExists->first()) {
                    return;
                } else {
                    $newQuoteCustomer = new QuoteCustomer();
                    $newQuoteCustomer->cdb_id = $cdbId;
                    $newQuoteCustomer->customer_id = $customer->id;
                    $newQuoteCustomer->save();
                    Log::info('Saved in quote customer with Customer Id-> ' . $customer->id . ' , CDB Id ->' . $cdbId);
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            DB::disconnect('mysql');
        }
    }

    public static function findQuoteCustomerById($id)
    {
        return QuoteCustomer::where('customer_id', '=', $id)->get();
    }
}
