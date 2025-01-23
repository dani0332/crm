<?php

namespace App\Jobs\CarLost;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Services\SIBService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CarSoldResubmissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $backoff = 360;
    public $tries = 3;

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
        $quotes = CarQuote::whereHas('carLostQuoteLog', function ($q) {
            $q->where('quote_status_id', QuoteStatusEnum::CarSold)
                ->whereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()])
                ->where('status', GenericRequestEnum::PENDING);
        })->with(['carLostQuoteLog', 'advisor.managers', 'carLostQuoteLogs'])
            ->withCount('carLostQuoteLogs')
            ->having('car_lost_quote_logs_count', '>=', 2)
            ->get();

        if ($quotes->count() <= 0) {
            info('CarSoldResubmissions - no quotes available to send resubmission reminder');

            return true;
        }

        $advisors = $quotes->pluck('advisor.email')->toArray();
        $managers = $quotes->pluck('advisor.managers.*.email')->unique()->flatten()->all();

        $storage = ApplicationStorage::whereIn('key_name', [ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TO, ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_CC, ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TEMPLATE])
            ->get()
            ->keyBy('key_name');

        $to = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TO]->value;
        $cc = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_CC]->value;

        // group all cc recipients, advisors -> managers,
        $cc = implode(',', array_merge([$cc], $advisors, $managers));

        $templateId = $storage[ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TEMPLATE]->value;

        $emailData = [];

        foreach ($quotes as $quote) {
            $emailData['quotes'][] = [
                'uuid' => $quote->uuid,
                'advisor_name' => $quote->advisor->name,
                'batch' => $quote->renewal_batch,
                'submission_date' => Carbon::parse($quote->carLostQuoteLogs[0]->created_at)->format('jS M Y'),
                'resubmission_date' => Carbon::parse($quote->carLostQuoteLog->created_at)->format('jS M Y'),
                'quote_url' => env('APP_URL').'/quotes/car/'.$quote->uuid,
                'highlight' => (in_array(Carbon::parse($quote->carLostQuoteLog->created_at)->format('d'), [15, 16, 17])),
            ];
        }

        info('CarSoldResubmissions - Sending Car Sold Resubmissions email total Leads: '.$quotes->count());

        SIBService::sendEmailUsingSIB(intval($templateId), $emailData, '', $to, $cc);

        info('CarSoldResubmissions - Car Sold Resubmissions email sent');

    }

    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Car Solde submission reminder Job Failed. Error: '.$exception->getMessage());
    }
}
