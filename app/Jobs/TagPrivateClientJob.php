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

class TagPrivateClientJob implements ShouldQueue
{
    use PrivateClient, Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $quotes = PersonalQuote::with('customer')->whereNull('pc_qualified')
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->whereIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Life, QuoteTypeId::Yacht]);

            $quotes = $quotes->orderBy('created_at', 'asc')->get();

            if ($quotes->isEmpty()) {
                LoggerService::info(self::class.': No quotes found without PCP tag');
            }

            foreach ($quotes as $quote) {

                $customerData = [
                    'customer_id' => $quote->customer->id,
                    'customer_name' => $quote->customer->first_name.' '.$quote->customer->last_name,
                    'email' => $quote->customer->email,
                ];

                LoggerService::info(self::class.': Private client tag marking activity started', extra: $customerData);

                LoggerService::startQuoteLogging(QuoteTypes::getName($quote->quote_type_id)->refId($quote->uuid), LoggerFeatureEnum::PCP_CLIENT);
                $this->applyPcpTag($quote->uuid, $quote->quote_type_id);
                LoggerService::endLogging();

                LoggerService::info(self::class.': Private client tag marking activity completed', extra: $customerData);
            }

            LoggerService::info(self::class.': Private client tag exercise has been completed');

        } catch (\Exception $e) {
            LoggerService::error(self::class.': Private client tagging exercise failed', exception: $e);
            throw $e;
        }
    }
}
