<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use Illuminate\Console\Command;

class UpdateLostStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateLostStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update the lost status of the leads which are not modified for more than 120 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Need to verify status for all quote types which were included or excluded.
        $lostReasonId = 34; //Stale for more than 90 days
        $eligibleQuoteTypes = [
            CarQuote::class,
            HomeQuote::class,
            HealthQuote::class,
            LifeQuote::class,
            BusinessQuote::class,
            PersonalQuote::class,
            TravelQuote::class
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

        info("------------------- Update Lost Status Command Started At: " . now() . " -------------------");

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info("------------------- Updating : " . $eligibleQuoteType . " -------------------");
            $eligibleQuoteType::whereNotIn('quote_status_id', $skipStatus)
                ->where('updated_at', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-120 days')))
                ->when($eligibleQuoteType == CarQuote::class, function ($carQuote) {
                    $carQuote->with('carQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == HomeQuote::class, function ($homeQuote) {
                    $homeQuote->with('homeQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == HealthQuote::class, function ($healthQuote) {
                    $healthQuote->with('healthQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == LifeQuote::class, function ($lifeQuote) {
                    $lifeQuote->with('lifeQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
                    $businessQuote->with('businessQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
                    $personalQuote->with('quoteDetail');
                })
                ->when($eligibleQuoteType == TravelQuote::class, function ($travelQuote) {
                    $travelQuote->with('travelQuoteRequestDetail');
                })
                ->chunkById(1000, function ($quoteDetails) use ($eligibleQuoteType, $lostReasonId){
                    foreach ($quoteDetails as $quoteDetail) {
                        $quoteDetail->update([
                            'quote_status_id' => QuoteStatusEnum::Lost
                        ]);

                        info("Quote Found-" . $eligibleQuoteType. " - Quote ID: $quoteDetail->id - Quote Ref-ID: $quoteDetail->code - Old Status: $quoteDetail->quote_status_id - New Status: " . QuoteStatusEnum::Lost . " - Updated At: $quoteDetail->updated_at");
                        Audit::create([
                            'event' => 'updated',
                            'auditable_type' => $eligibleQuoteType,
                            'auditable_id' => $quoteDetail->id,
                            'old_values' => ['quote_status_id' => $quoteDetail->quote_status_id],
                            'new_values' => ['quote_status_id' => QuoteStatusEnum::Lost, 'notes' => 'Lead not modified for more than 120 days'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        switch ($eligibleQuoteType) {
                            case CarQuote::class:
                                $quoteDetail->carQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case HomeQuote::class:
                                $quoteDetail->homeQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case HealthQuote::class:
                                $quoteDetail->healthQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case LifeQuote::class:
                                $quoteDetail->lifeQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case BusinessQuote::class:
                                $quoteDetail->businessQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case PersonalQuote::class:
                                $quoteDetail->quoteDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            case TravelQuote::class:
                                $quoteDetail->travelQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                                break;
                            default:
                                continue;
                                break;
                        }
                    }
                });
            info("------------------- Updated : " . $eligibleQuoteType . " -------------------");


    }
}
