<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\Revival\HomeRevivalLeadsCreationJob;
use App\Models\PersonalQuote;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class DttHome extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DttHome';

    private const DELAY_IN_SECONDS = 30;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run the home leads revival process';

    /**
     * Execute the console command.
     */
    public function handle(): ?bool
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HOME_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::info(self::class.' - DTT Home Revival is not enabled from cms');

            return false;
        }

        LoggerService::startFeatureLogging(LoggerFeatureEnum::HOME_REVIVAL);
        LoggerService::info(self::class.' - handle - starting home revival command');

        // SHORT REVIVAL: leads created 90 days ago
        $shortRevivalLeads = PersonalQuote::query()
            ->select(['id', 'uuid', 'quote_type_id', 'email', 'mobile_no'])
            ->where('quote_type_id', QuoteTypeId::Home)
            ->whereHas('homeQuote')
            // for prod
            // ->whereDate('created_at', now()->subDays(90)->toDateString())

            // for stage
            ->whereBetween('created_at', [now()->subMinutes(25), now()->subMinutes(5)])
            ->where('is_revived', false)
            ->whereNotIn('source', [
                LeadSourceEnum::REVIVAL_SHORT,
                LeadSourceEnum::REVIVAL_ANNUAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
                LeadSourceEnum::RENEWAL_UPLOAD,
            ])
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::TransactionApproved,
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::PolicyBooked,
                QuoteStatusEnum::POLICY_BOOKING_QUEUED,
                QuoteStatusEnum::POLICY_BOOKING_FAILED,
                QuoteStatusEnum::PolicySentToCustomer,
            ])
            ->where(function ($query): void {
                $query->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)
                    ->orWhereNull('payment_status_id');
            })
            ->where(function ($query): void {
                $query->where('updated_at', '<', now()->subDays(15))
                    ->orWhereNotIn('quote_status_id', [
                        QuoteStatusEnum::Quoted,
                        QuoteStatusEnum::FollowedUp,
                    ]);
            })
            ->get();

        // ANNUAL REVIVAL: leads created 10 months ago
        $annualRevivalLeads = PersonalQuote::query()
            ->select(['id', 'uuid', 'quote_type_id', 'email', 'mobile_no'])
            ->where('quote_type_id', QuoteTypeId::Home)
            ->whereHas('homeQuote')
            // for prod
            // ->whereDate('created_at', now()->subMonths(10)->toDateString())

            // for stage
            ->whereBetween('created_at', [now()->subMinutes(45), now()->subMinutes(25)])
            ->where('is_annual_revived', false)
            ->whereNotIn('source', [
                LeadSourceEnum::REVIVAL_SHORT,
                LeadSourceEnum::REVIVAL_ANNUAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
                LeadSourceEnum::RENEWAL_UPLOAD,
            ])
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::TransactionApproved,
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::PolicyBooked,
                QuoteStatusEnum::POLICY_BOOKING_QUEUED,
                QuoteStatusEnum::POLICY_BOOKING_FAILED,
                QuoteStatusEnum::PolicySentToCustomer,
            ])
            ->where(function ($query): void {
                $query->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)
                    ->orWhereNull('payment_status_id');
            })
            ->whereShortRevivalNotConverted()
            ->get();

        LoggerService::info(self::class.' - Short revival leads count: '.$shortRevivalLeads->count());
        LoggerService::info(self::class.' - Annual revival leads count: '.$annualRevivalLeads->count());

        $this->dispatchRevivalBatch($shortRevivalLeads, LeadSourceEnum::REVIVAL_SHORT, 'Home DTT Short Revival Batch Jobs');
        $this->dispatchRevivalBatch($annualRevivalLeads, LeadSourceEnum::REVIVAL_ANNUAL, 'Home DTT Annual Revival Batch Jobs');

        return null;
    }

    private function dispatchRevivalBatch(Collection $leads, string $source, string $batchName): void
    {
        if ($leads->isEmpty()) {
            LoggerService::info(self::class.' - No leads found for batch: '.$batchName);

            return;
        }

        $jobs = [];
        $delayCounter = 0;

        foreach ($leads as $lead) {
            LoggerService::info(self::class.' - Queuing Home Revival Lead Job for lead '.$lead->uuid.'with source '.$source.' in batch: '.$batchName);
            $jobs[] = (new HomeRevivalLeadsCreationJob($lead->id, $source))->delay(now()->addSeconds(self::DELAY_IN_SECONDS + $delayCounter));
            $delayCounter += self::DELAY_IN_SECONDS;
        }

        Bus::batch($jobs)
            ->then(function () use ($batchName): void {
                LoggerService::info(self::class.' - all jobs completed successfully for batch: '.$batchName);
            })
            ->catch(function () use ($batchName): void {
                LoggerService::warning(self::class.' - one or more jobs failed for batch: '.$batchName);
            })
            ->finally(function () use ($batchName): void {
                LoggerService::info(self::class.' - batch finished: '.$batchName);
            })
            ->allowFailures()
            ->name($batchName)
            ->dispatch();
    }
}
