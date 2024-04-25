<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\CarRevivalLeadsCreationJob;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Carbon\Carbon;
use Exception;
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
    protected $description = 'This cron will fetch the leads 11 months && 1 year 11 months ago from the car quotes according to given criteria';

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

            $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
            if (! $isDttEnabled) {
                throw new Exception('Dtt is not enabled from cms');
            }
            $dateOne = Carbon::now()->subMonths(11)->toDateString();
            $dateTwo = Carbon::now()->subYear(1)->subMonths(11)->toDateString();

            $datethirtyDaysBefore = Carbon::now()->subDays(30)->toDateString();

            $jobs = [];
            $leads = CarQuote::where('is_revived', '=', false)
                ->where(function ($q) use ($dateOne, $dateTwo) {
                    $q->whereDate('created_at', '=', $dateOne);
                    $q->orWhereDate('created_at', '=', $dateTwo);
                })

                ->whereNotNull(['email'])
                ->where(function ($q) use ($datethirtyDaysBefore) {
                    $q->where('source', '!=', LeadSourceEnum::REVIVAL)
                        ->where('created_at', '<=', $datethirtyDaysBefore);
                })
                ->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->whereNull('renewal_batch')
                ->whereNull('previous_quote_policy_number')

                ->whereNotIn('quote_status_id', [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved])
                ->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)

                ->groupBy(['email', 'car_make_id', 'car_model_id', 'year_of_manufacture'])
                ->orderBy('id', 'DESC')
                ->get();

            info('------CarRevivalLeadsCreationJobCount --'.count($leads).'------'.json_encode($leads->pluck('uuid')->toArray()));

            foreach ($leads as $carLead) {
                $isTierR = $this->leadAllocationService->checkIfLeadIsRenewal($carLead);
                info('------isTierR --'.! $isTierR);
                if (! $isTierR) {
                    $jobs[] = new CarRevivalLeadsCreationJob($carLead);
                }
            }
            $logPrefix = '------CarRevivalLeadsCreationJob------';
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
            } else {
                info('------No lead Found------');
            }
        } catch (\Exception $exception) {
            info('DTT Exception : '.$exception->getMessage());
        }
    }
}
