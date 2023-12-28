<?php

namespace App\Console\Commands;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Illuminate\Console\Command;

class UpdateStaleLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateStaleLeads:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the leads with flag Stale when last modified date is greater than 30 days. This should not be applied on leads which having
    status "Transaction approved", "Policy Documents Pending", "Policy issued", "Policy sent to customer", "Policy booked", "LOST", "FAKE", "Duplicate", "Cancellation Pending", "Policy Cancelled"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eligibleQuoteTypes = [
            HealthQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            PersonalQuote::class
        ];

        $skipStatus = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::Lost,
            QuoteStatusEnum::Fake,
            QuoteStatusEnum::Duplicate,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled
        ];

        info("------------------- Update Stale Leads Command Started At: ".now()." -------------------");

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {
            info("------------------- Updating : ".$eligibleQuoteType." -------------------");
            $eligibleQuoteType::whereNotIn('quote_status_id', $skipStatus)
                ->where('updated_at', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')))
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote){
                    $businessQuote->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote){
                    $personalQuote->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle]);
                })->chunkById(100, function ($quoteDetails){
                    foreach ($quoteDetails as $quoteDetail) {
                        $quoteDetail->update([
                            'is_stale' => true,
                            'stale_at' => now()
                        ]);
                    }
                });
            info("------------------- Updated : ".$eligibleQuoteType." -------------------");
        }

        info("------------------- Update Stale Leads Command Finished for ".now()." -------------------");

    }

}
