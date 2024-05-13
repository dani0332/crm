<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\CarRevivalLeadsCreationJob;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
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
    protected $description = 'This cron will fetch the leads 11 months && 1 year 11 months ago from the car quotes according to given criteria';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
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
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT is not enabled from cms');

            return false;
        }
        $dttInProgress = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_REVIVAL_IN_PROGRESS);
        if ($dttInProgress == 1) {

            info('DTT already in progress');

            return false;
        }

        $dateOne = Carbon::now()->subMonths(11)->toDateString();

        $datethirtyDaysBefore = Carbon::now()->subDays(30)->toDateString();

        $excludeSources = ['AFIA Renewal', 'afia.ae enquiry', 'AQEED_LEAD', 'AQEED_RENEWALS', 'AQEED_REVIVAL', 'ARABIC_ADVISORY', 'ARABIC_CALL_DESK', 'ARABIC_TELE_MARKETING', 'asd', 'BDM', 'CALL_DESK', 'CALL_DESK_WHATSAPP', 'car form', 'carinsurance.ae', 'CarVault- Affinity Motor',
            'CORPLINE_NB', 'CROSS_SELL', 'DUBAI_NOW', 'ECOM', 'Enquiry from reception (not an existing client)', 'Existing clients new business', 'Home - InsuranceMarket.ae', 'IM_PRIO', 'IMCRM', 'Medical & Life - InsuranceMarket.ae', 'Mobile', 'Motor Enquiry on InsuranceMarket.ae',
            'Motor inquiry on Protectmycar.ae', 'Motor inquiry on zoompolicy.com', 'My personal contact (not an existing client)', 'postman', 'RECYCLED', 'REFERRAL',
            'Referral from an existing client', 'Renewal_upload', 'REVIVAL', 'Revived Lead - InsuranceMarket.ae', 'test', 'test_postman', 'TIER_L_FUTUREDATELEADS', 'TIER_L_QUALFIED', 'TM_FACEBOOK', 'TM_OFFSHORE', 'TM_ORGANIC', 'TM_RENEWALS',
            'TM_SP_RENEWAL', 'TM_WHATSAPP', 'TPL_COMP', 'TPL_Renewal', 'TPL_RENEWALS', 'Travel - InsuranceMarket.ae', 'Walk In Client (not an existing client)', 'web', LeadSourceEnum::RENEWAL_UPLOAD,
        ];

        $jobs = [];
        $logPrefix = 'CarRevivalLeadsCreationJob -';
        $leads = CarQuote::where('is_revived', '=', false)
            ->whereDate('created_at', '=', $dateOne)

            ->whereNotNull(['email'])
            ->where(function ($q) use ($datethirtyDaysBefore) {
                $q->where('source', '!=', LeadSourceEnum::REVIVAL)
                    ->where('created_at', '<=', $datethirtyDaysBefore);
            })
            ->whereNotIn('source', $excludeSources)
            ->whereNull('renewal_batch')
            ->whereNull('previous_quote_policy_number')

            ->whereNotIn('quote_status_id', [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved])
            ->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)

            ->groupBy(['email', 'car_make_id', 'car_model_id', 'year_of_manufacture'])
            ->get();

        info($logPrefix.' count - '.count($leads).' - '.json_encode($leads->pluck('uuid')->toArray()));

        foreach ($leads as $carLead) {
            $isTierR = app(LeadAllocationService::class)->checkIfLeadIsRenewal($carLead);
            if (! $isTierR) {
                $jobs[] = new CarRevivalLeadsCreationJob($carLead);
            }
        }

        if ($jobs != null && count($jobs)) {

            ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_REVIVAL_IN_PROGRESS)->update(['value' => true]);
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix.' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix.' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {

                    ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_REVIVAL_IN_PROGRESS)->update(['value' => false]);
                    info($logPrefix.' everything done');
                })
                ->allowFailures()
                ->withDelay(30)
                ->dispatch();
        } else {
            info($logPrefix.'------No lead Found------');
        }
    }
}
