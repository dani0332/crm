<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\AllocationFactory;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Services\ApplicationStorageService;
use Illuminate\Console\Command;

class QuoteAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to check if any unassigned is available then assign them accordingly';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ApplicationStorageService $applicationStorageService)
    {
        $currentIteration = now();
        info("------------------- Quote Allocation Command Started At: $currentIteration -------------------");

        $quoteAllocationSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH);
        $masterSwitchConfigValue = (int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH');

        if ($quoteAllocationSwitch == 1 && $masterSwitchConfigValue == 1) {
            $to = now()->subMinutes(7)->toDateTimeString();
            $chunkSize = 50;
            $linesOfBusiness = [
                QuoteTypeId::Car => [
                    'model' => CarQuote::class,
                    'allocationKey' => 'advisor_id',
                    'conditions' => function ($lead) {
                        return $lead instanceof CarQuote
                            && ! in_array($lead->quote_status_id, [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                            && ! in_array($lead->source, [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
                            && $lead->is_renewal_tier_email_sent === 0;
                    },
                ],
                QuoteTypeId::Health => [
                    'model' => HealthQuote::class,
                    'allocationKey' => 'advisor_id',
                    'conditions' => function ($lead) {
                        return $lead instanceof HealthQuote
                            && $lead->quote_status_id === QuoteStatusEnum::Qualified
                            && $lead->health_quote_request->price_starting_from !== null
                            && ! $lead->health_quote_request->is_error_email_sent
                            && $lead->health_quote_request->advisor_id === null;
                    },
                ],
            ];

            foreach ($linesOfBusiness as $quoteType => $config) {
                $this->executeQuoteAllocation($quoteType, $config, $to, $chunkSize);
            }

        } else {
            info('Quote Allocation Command is turned Off');
        }

        info("------------------- Quote Allocation Command Finished for $currentIteration -------------------");
    }

    public function executeQuoteAllocation($quoteType, $config, $to, $chunkSize)
    {
        $quoteModel = $config['model'];
        $allocationKey = $config['allocationKey'];
        $conditions = $config['conditions'];
        $processedRecords = 0;
        $quoteModel::whereNull($allocationKey)
            ->whereBetween('created_at', [now()->startOfDay()->toDateTimeString(), $to])
            ->when($conditions, fn ($query) => $query->where($conditions))
            ->chunk($chunkSize, function ($leads) use ($quoteType, $processedRecords) {
                foreach ($leads as $lead) {
                    info('------ Lead allocation started for '.QuoteTypeId::getDescription($quoteType)." lead: $lead->uuid ------");
                    $allocationStrategy = AllocationFactory::createStrategy($quoteType, $lead->id);
                    $allocationStrategy->executeSteps();
                    $processedRecords++;
                }
            });
        if ($processedRecords === 0) {
            info('No records found for '.QuoteTypeId::getDescription($quoteType));
        }
        info('------ Lead allocation end for '.QuoteTypeId::getDescription($quoteType).'  ------');
    }
}
