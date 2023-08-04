<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Services\SIBService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CarSoldResubmissions //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $quotes = CarQuote::whereHas('carLostQuoteLog', function($q) {
            $q->where('quote_status_id', QuoteStatusEnum::CarSold)
                ->whereDate('created_at', Carbon::yesterday())
                ->where('status', GenericRequestEnum::PENDING);
        })->with(['carLostQuoteLog.actionBy','advisor.managers'])
            ->withCount('carLostQuoteLogs')
            ->having('car_lost_quote_logs_count', '>=' , 2)
            ->get();

        $advisors = $quotes->pluck('advisor.email')->toArray();
        $managers = $quotes->pluck('advisor.managers.*.email')->unique()->flatten()->all();

        $storage = ApplicationStorage::whereIn('key_name', [ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TO, ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_CC, ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TEMPLATE])
            ->get()
            ->keyBy('key_name');

        $to = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TO]->value;
        $cc = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_CC]->value;

        //group all cc recipients, advisors -> managers,
        $cc = implode(',', array_merge([$cc], $advisors, $managers));

        //dd($to, $cc);
        $templateId = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TEMPLATE]->value;

        $emailData = [
            'name' => 'test'
        ];

        info('Sending Car Sold Resubmissions email total Leads: ' . $quotes->count());

        SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

        info('Car Sold Resubmissions email sent');

    }
}
