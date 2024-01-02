<?php

namespace App\Console\Commands;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Carbon\Carbon;
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

        info("------------------- Update Stale Leads Command Started At: " . now() . " -------------------");

        $lostReasonId = 34; //Stale for more than 90 days
        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info("------------------- Updating : " . $eligibleQuoteType . " -------------------");
            $eligibleQuoteType::whereNotIn('quote_status_id', $skipStatus)
                ->where('updated_at', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')))
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
                    $businessQuote->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
                    $personalQuote->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle]);
                })->chunkById(100, function ($quoteDetails) {
                    foreach ($quoteDetails as $quoteDetail) {
                        $quoteDetail->update([
                            'stale_at' => now()
                        ]);
                    }
                });
            info("------------------- Updated : " . $eligibleQuoteType . " -------------------");

            info("------------------- Updating Lost Status on Stale Leads for: " . $eligibleQuoteType . " -------------------");
            $eligibleQuoteType::with('activities')
                ->when($eligibleQuoteType == HealthQuote::class, function ($healthQuote) {
                    $healthQuote->with('healthQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
                    $businessQuote->with('businessQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == HomeQuote::class, function ($homeQuote) {
                    $homeQuote->with('homeQuoteRequestDetail');
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
                    $personalQuote->with('quoteDetail');
                })
                ->whereNotNull('stale_at')
                ->where('stale_at', '<', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days')))
                ->chunkById(100, function ($staleLeads) use ($eligibleQuoteType, $lostReasonId) {
                    foreach ($staleLeads as $staleLead) {

                        $activityDateCheck = $staleLead->activities->pluck('due_date')->contains(function ($value) {
                            return Carbon::createFromFormat(config('constants.DATE_FORMAT_ONLY'), Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY')))->gt(Carbon::now());
                        });

                        // This check not included in FR but think should be included
                        $activityStatusCheck = $staleLead->activities->pluck('status')->contains(function ($value) {
                            return $value == 0;
                        });

                        if (!$activityDateCheck) {
                            // Should be updated in History or Audit logs
                            // Need to make hardcode id into constant
                            $staleLead->update([
                                'quote_status_id' => QuoteStatusEnum::Lost
                            ]);

                            if($eligibleQuoteType == HealthQuote::class) {
                                $staleLead->healthQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                            }

                            if($eligibleQuoteType == BusinessQuote::class) {
                                $staleLead->businessQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                            }

                            if($eligibleQuoteType == HomeQuote::class) {
                                $staleLead->homeQuoteRequestDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                            }

                            if($eligibleQuoteType == PersonalQuote::class) {
                                $staleLead->quoteDetail->update([
                                    'lost_reason_id' => $lostReasonId
                                ]);
                            }
                        }
                    }
                });
            info("------------------- Updated Lost Status on Stale Leads for: " . $eligibleQuoteType . " -------------------");
        }

        info("------------------- Update Stale Leads Command Finished for " . now() . " -------------------");
    }
}
