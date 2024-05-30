<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\HealthRevivalLeadsCreationJob;
use App\Models\HealthQuote;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DttHealth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will fetch the leads 11 months from the health quotes according to given criteria';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT is not enabled from cms');

            return false;
        }

        $dateOne = Carbon::now()->subMonths(11)->toDateString();

        $logPrefix = 'HealthRevivalLeadsCreationJob-';
        DB::enableQueryLog();
        $leads = HealthQuote::whereDate('created_at', '=', $dateOne)


            ->whereNotIn('quote_status_id', [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved])

            ->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)
            ->whereHas('healthQuoteRequestDetail', function ($q) {
                $q->whereNull('transapp_code');
            })
            ->with(['healthQuoteRequestDetail' => function ($q) {
                $q->whereNull('transapp_code');
            }])
            ->get();
        dd(DB::getQueryLog());
        dd(count($leads));


        foreach ($leads as $item) {
            $jobs[] = new HealthRevivalLeadsCreationJob($item);
        }

        if ($jobs != null && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix . ' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix . ' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {
                    info($logPrefix . ' everything done');
                })
                ->allowFailures()
                ->withDelay(5)
                ->dispatch();
        } else {
            info($logPrefix . '------No lead Found------');
        }
    }
}
