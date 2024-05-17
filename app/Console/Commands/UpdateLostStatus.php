<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

class UpdateLostStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateLostStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Lost status on leads which quote status are not updated from last 120 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lostReasonId = QuoteStatusEnum::LOSTREASONID; //Stale for more than 90 days
        $eligibleQuoteTypes = [
            CarQuote::class,
            HomeQuote::class,
            HealthQuote::class,
            LifeQuote::class,
            BusinessQuote::class,
            PersonalQuote::class,
            TravelQuote::class,
        ];

        $skipStatus = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::CancellationPending,
        ];

        info('------------------- Update Lost Status Command Started At: '.now().' -------------------');

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info('------------------- Update Lost Status Command - Updating : '.$eligibleQuoteType.' -------------------');
            $eligibleQuoteType::whereNotIn('quote_status_id', $skipStatus)
                ->where('quote_status_date', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-120 days')))
                ->chunkById(1000, function ($quoteDetails) use ($eligibleQuoteType, $lostReasonId) {
                    foreach ($quoteDetails as $quoteDetail) {
                        $quoteDetail->update([
                            'quote_status_id' => QuoteStatusEnum::Lost,
                            'quote_status_date' => now(),
                        ]);

                        info('Quote Found-'.$eligibleQuoteType." - Quote Ref-ID: $quoteDetail->code - Old Status: $quoteDetail->quote_status_id - New Status: ".QuoteStatusEnum::Lost." - Updated At: $quoteDetail->updated_at");
                        Audit::create([
                            'event' => 'updated',
                            'auditable_type' => $eligibleQuoteType,
                            'auditable_id' => $quoteDetail->id,
                            'old_values' => ['quote_status_id' => $quoteDetail->quote_status_id],
                            'new_values' => ['quote_status_id' => QuoteStatusEnum::Lost, 'notes' => 'Lead not modified for more than 120 days'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        switch ($eligibleQuoteType) {
                            case CarQuote::class:
                                CarQuoteRequestDetail::updateOrCreate(['car_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case HomeQuote::class:
                                HomeQuoteRequestDetail::updateOrCreate(['home_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case HealthQuote::class:
                                HealthQuoteRequestDetail::updateOrCreate(['health_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case LifeQuote::class:
                                LifeQuoteRequestDetail::updateOrCreate(['life_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case BusinessQuote::class:
                                BusinessQuoteRequestDetail::updateOrCreate(['business_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case PersonalQuote::class:
                                PersonalQuoteDetail::updateOrCreate(['personal_quote_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;

                            case TravelQuote::class:
                                TravelQuoteRequestDetail::updateOrCreate(['travel_quote_request_id' => $quoteDetail->id], ['lost_reason_id' => $lostReasonId]);
                                break;
                            default:
                                break;
                        }
                    }
                });
            info('------------------- Update Lost Status Command - Updated : '.$eligibleQuoteType.' -------------------');
        }
    }
}
