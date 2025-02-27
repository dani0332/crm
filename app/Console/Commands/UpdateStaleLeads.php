<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
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
    protected $description = 'Update status Stale on leads which quote status are not updated from last 30 days. It should not apply on leads which having status
        Transaction Approved, Policy Documents Pending, Policy Issued, Policy sent to Customer, Policy Booked, Lost, Fake, Duplicate, Cancellation Pending, Policy Cancelled
        and source is inlsy.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specifiedDate = Carbon::parse('2024-06-22 23:59:59');
        $currentDate = Carbon::now();

        if ($currentDate->lessThan($specifiedDate)) {
            info('UpdateStaleLeads Command will run after 2024-06-22 23:59:59');

            return;
        }

        // Need to verify status for all quote types which were included or excluded.
        $eligibleQuoteTypes = [
            HealthQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            PersonalQuote::class,
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
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        $skipStatusInLost = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        info('------------------- Update Stale Leads Command Started At: '.now().' -------------------');

        $lostReasonId = QuoteStatusEnum::LOSTREASONID; // Stale for more than 90 days
        foreach ($eligibleQuoteTypes as $eligibleQuoteType) {

            info('------------------- Update Stale Leads Command - Updating - '.now().' : '.$eligibleQuoteType.' -------------------');
            $eligibleQuoteType::whereNotIn('quote_status_id', $skipStatus)
                ->whereNot('source', LeadSourceEnum::INSLY)
                ->where('quote_status_date', '<', Carbon::parse(date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')))->endOfDay())
                ->where('quote_status_date', '>=', Carbon::parse('2023-05-23')->startOfDay())
                ->when($eligibleQuoteType == BusinessQuote::class, function ($businessQuote) {
                    $businessQuote->whereNot('business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                })
                ->when($eligibleQuoteType == PersonalQuote::class, function ($personalQuote) {
                    $personalQuote->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle]);
                })->chunkById(1000, function ($quoteDetails) {
                    foreach ($quoteDetails as $quoteDetail) {
                        if (! isset($quoteDetail->stale_at)) {
                            $quoteDetail->update([
                                'stale_at' => now(),
                            ]);
                        }
                    }
                });
            info('------------------- Update Stale Leads Command - Updated - '.now().' : '.$eligibleQuoteType.' -------------------');

            info('------------------- Updating Lost Status on Stale Leads for: '.$eligibleQuoteType.' -------------------');
            $eligibleQuoteType::with('activities')
                ->whereNotIn('quote_status_id', $skipStatusInLost)
                ->whereNotNull('stale_at')
                ->where('stale_at', '<', Carbon::parse(date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days')))->endOfDay())
                ->chunkById(1000, function ($staleLeads) use ($eligibleQuoteType, $lostReasonId) {
                    foreach ($staleLeads as $staleLead) {
                        $activityDateCheck = $staleLead->activities->pluck('due_date')->contains(function ($value) {
                            return Carbon::createFromFormat(config('constants.DATE_FORMAT_ONLY'), Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY')))->gt(Carbon::now());
                        });

                        if (! $activityDateCheck) {
                            $staleLead->update([
                                'quote_status_id' => QuoteStatusEnum::Lost,
                                'quote_status_date' => now(),
                                'stale_at' => null,
                            ]);

                            info('Quote Found - '.$eligibleQuoteType." - Quote Ref-ID: $staleLead->code - Old Status: $staleLead->quote_status_id - New Status: ".QuoteStatusEnum::Lost." - Updated At: $staleLead->updated_at");
                            Audit::create([
                                'event' => 'updated',
                                'auditable_type' => $eligibleQuoteType,
                                'auditable_id' => $staleLead->id,
                                'old_values' => ['quote_status_id' => $staleLead->quote_status_id],
                                'new_values' => ['quote_status_id' => QuoteStatusEnum::Lost, 'notes' => 'Stale for more than 90 days'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            switch ($eligibleQuoteType) {
                                case HomeQuote::class:
                                    HomeQuoteRequestDetail::updateOrCreate(['home_quote_request_id' => $staleLead->id], ['lost_reason_id' => $lostReasonId]);
                                    break;
                                case HealthQuote::class:
                                    HealthQuoteRequestDetail::updateOrCreate(['health_quote_request_id' => $staleLead->id], ['lost_reason_id' => $lostReasonId]);
                                    break;
                                case BusinessQuote::class:
                                    BusinessQuoteRequestDetail::updateOrCreate(['business_quote_request_id' => $staleLead->id], ['lost_reason_id' => $lostReasonId]);
                                    break;
                                case PersonalQuote::class:
                                    PersonalQuoteDetail::updateOrCreate(['personal_quote_id' => $staleLead->id], ['lost_reason_id' => $lostReasonId]);
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                });
            info('------------------- Updated Lost Status on Stale Leads for: '.$eligibleQuoteType.' -------------------');
        }

        info('------------------- Update Stale Leads Command Finished for '.now().' -------------------');
    }
}
