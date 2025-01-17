<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\Revival\HealthRevivalLeadsCreationJob;
use App\Models\HealthQuote;
use App\Models\Transaction;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HEALTH_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT_HEALTH is not enabled from cms');

            return false;
        }

        $dateOne = Carbon::now()->subMonths(11)->toDateString();
        $dateTwo = Carbon::now()->subMonths(11)->addDay(1)->toDateString();
        $logPrefix = 'DTTHealth - RevivalLeadsCreationJob -';
        $excludeSources = [
            LeadSourceEnum::REVIVAL,
        ];
        $leads = HealthQuote::select(
            'id',
            'uuid',
            'first_name',
            'last_name',
            'gender',
            'email',
            'dob',
            'details',
            'mobile_no',
            'preference',
            'marital_status_id',
            'premium',
            'lead_type_id',
            'is_ebp_renewal',
            'cover_for_id',
            'has_dental',
            'has_worldwide_cover',
            'has_home',
            'currently_insured_with_id',
            'emirate_of_your_visa_id',
            'price_starting_from',
            'nationality_id',
            'salary_band_id',
            'member_category_id',
            'customer_id',
            'health_team_type',
            'health_plan_type_id'
        )
            ->where('is_revived', '=', false)
            ->where('created_at', '>=', $dateOne)
            ->where('created_at', '<', $dateTwo)
            ->whereNotIn('source', $excludeSources)
            ->where(function ($q) {
                $q->whereNotIn('quote_status_id', [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved]);
                $q->orWhereNull('quote_status_id');
            })
            ->where(function ($q) {
                $q->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED);
                $q->orWhereNull('payment_status_id');
            })
            ->whereHas('healthQuoteRequestDetail', function ($q) {
                $q->where('transapp_code', '');
                $q->orWhereNull('transapp_code');
            })
            ->get();

        if ($leads->count() == 0) {
            info($logPrefix.'No leads found');

            return false;
        }

        $customer_ids = $leads->pluck('customer_id')->toArray();
        $customerIdsWithTransApp = [];
        foreach ($customer_ids as $customer_id) {

            if (Transaction::where('customer_id', $customer_id)->exists()) {
                $customerIdsWithTransApp[] = $customer_id;
            }
        }

        $filteredLeads = $leads->filter(function ($item) use ($customerIdsWithTransApp) {
            return in_array($item->customer_id, $customerIdsWithTransApp) ? false : true;
        });

        info($logPrefix.' count - '.count($filteredLeads).' - '.json_encode($filteredLeads->pluck('uuid')->toArray()));

        $jobs = [];
        foreach ($filteredLeads as $item) {
            $jobs[] = new HealthRevivalLeadsCreationJob($item);
        }

        if ($jobs != null && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix.' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix.' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {
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
