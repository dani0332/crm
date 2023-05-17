<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\CarRevivalLeadsCreationJob;
use App\Models\CarQuote;
use App\Services\LeadAllocationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class Dtt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will fetch the leads 1 year ago (- 15 days) from the car quotes according to given criteria';

    private $leadAllocationService = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *f.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $date = Carbon::now()->subYear(1)->subDays(15)->toDateString();

            $datethirtyDaysBefore = Carbon::now()->addDays(-30)->toDateString();

            $jobs = [];

            $leads = CarQuote::where('created_at', '>=', $date)
                ->where('is_revived', '=', false)
                ->whereNotNull(['email', 'car_make_id', 'car_model_id', 'year_of_manufacture', 'payment_status_id'])
                ->where(function ($q) use ($datethirtyDaysBefore) {
                    $q->where('source', '!=', LeadSourceEnum::REVIVAL)
                        ->where('created_at', '<=', $datethirtyDaysBefore);
                })
                ->where(function ($q) {
                    $q->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                        ->orWhereNotNull('renewal_batch')
                        ->orWhereNotNull('previous_quote_policy_number')
                        ->orWhereNotNull('mobile_no');
                })
                ->where(function ($q) {
                    $q->whereNotIn('quote_status_id', [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved])
                        ->orWhere('payment_status_id', '!=', PaymentStatusEnum::CAPTURED);
                })
                ->groupBy(['email', 'car_make_id', 'car_model_id', 'year_of_manufacture'])
                ->take(1)
                ->orderBy('id', 'DESC')->get();

            foreach ($leads as $carLead) {
                $isTierR = $this->leadAllocationService->checkIfLeadIsRenewal($carLead);

                if (! $isTierR) {
                    $jobs[] = new CarRevivalLeadsCreationJob($carLead);
                }
            }
            $logPrefix = '------fn: createRevivedQuotes QuoteCreation started------';
            info($logPrefix);

            if ($jobs != null && count($jobs)) {
                Haystack::build()
                    ->addJobs($jobs)

                    ->then(function () use ($logPrefix) {
                        info('------'.$logPrefix.' all jobs completed successfully ------');
                    })
                    ->catch(function () use ($logPrefix) {
                        info('------'.$logPrefix.' one of batch is failed.------');
                    })
                    ->finally(function () use ($logPrefix) {
                        info('------'.$logPrefix.' everything done ------');
                    })
                    ->allowFailures()
                    ->withDelay(2)
                    ->dispatch();
            }
        } catch (\Exception $exception) {
            info('DTT Exception : '.$exception->getMessage());
        }
    }
}
