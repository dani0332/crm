<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TagPcpCustomerJob implements ShouldQueue
{
    use PrivateClient, Queueable;

    public array $uuids;

    public function __construct(array $uuids)
    {
        $this->uuids = $uuids;

        $this->onQueue('private-client');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info(self::class.': PC customer tag has been initiated');
        try {
            $quotesQuery = PersonalQuote::with('customer')
                ->whereNotNull('pc_qualified')
                ->whereRelation('customer', 'pcp_tag', false)
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->whereIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Life, QuoteTypeId::Yacht])
                ->orderBy('created_at', 'asc');

            // Add uuids to query if provided
            if (! empty($this->uuids)) {
                $quotesQuery->whereIn('uuid', $this->uuids);
            }

            $quotesQuery->chunk(200, function ($quotes) {
                foreach ($quotes as $quote) {

                    $customerData = [
                        'customer_id' => $quote->customer?->id,
                        'customer_name' => $quote->customer?->first_name.' '.$quote->customer?->last_name,
                        'email' => $quote->customer?->email,
                    ];

                    LoggerService::info(self::class.': PC customer tag marking activity started', extra: $customerData);

                    LoggerService::startQuoteLogging(QuoteTypes::getName($quote->quote_type_id)->refId($quote->uuid), LoggerFeatureEnum::PCP_CLIENT);
                    $this->applyPcpTag($quote->uuid, $quote->quote_type_id);
                    LoggerService::endLogging();

                    LoggerService::info(self::class.': PC customer tag marking activity completed', extra: $customerData);
                }
            });

            LoggerService::info(self::class.': PC customer tag exercise has been completed');

        } catch (\Exception $e) {
            LoggerService::error(self::class.': PC customer tagging exercise failed', exception: $e);
            throw $e;
        }
    }
}
