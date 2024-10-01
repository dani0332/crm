<?php

namespace App\Console\Commands;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

class UpdateStaleNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateStaleLeadsNotification:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status Stale on leads which quote status are not updated from last 30 days. It should not apply on leads which having status
        Transaction Approved, Policy Documents Pending, Policy Issued, Policy sent to Customer, Policy Booked, Lost, Fake, Duplicate, Cancellation Pending, Policy Cancelled.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Need to verify status for all quote types which were included or excluded.
        $eligibleQuoteTypes = [
            HealthQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            PersonalQuote::class,
        ];

        $statusToInclude = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        info('------------------- Update Stale Leads Command Started At: '.now().' -------------------');

        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info('------------------- Update Stale Leads Command - Updating - '.now().' : '.$eligibleQuoteType.' -------------------');
            $eligibleQuoteType::whereIn('quote_status_id', $statusToInclude)
                ->whereNotNull('stale_at')
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
                    $businessQuote->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
                    $personalQuote->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle]);
                })->chunkById(1000, function ($quoteDetails) {
                    foreach ($quoteDetails as $quoteDetail) {
                        if (isset($quoteDetail->stale_at)) {
                            $quoteDetail->update([
                                'stale_at' => null,
                            ]);
                        }
                    }
                });
            info('------------------- Update Stale Leads Command - Updated - '.now().' : '.$eligibleQuoteType.' -------------------');

        }

        info('------------------- Update Stale Leads Command Finished for '.now().' -------------------');
    }
}
