<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemovePcQualifiedJob implements ShouldQueue
{
    use PrivateClient, Queueable;

    public array $uuids;

    /**
     * Create a new job instance.
     */
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
        try {
            $quoteQuery = PersonalQuote::where('pc_qualified', true)
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->whereIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Life, QuoteTypeId::Yacht]);

            if (! empty($this->uuids)) {
                $quoteQuery->whereIn('uuid', $this->uuids);
            }

            $quotes = $quoteQuery->select('uuid', 'quote_type_id')->get();

            foreach ($quotes as $quote) {
                $this->removePcQualified($quote->uuid, $quote->quote_type_id);
            }

            LoggerService::info(self::class.': PC qualified removal exercise has been completed');
        } catch (\Exception $e) {
            LoggerService::error(self::class.': PC qualified removal exercise failed', exception: $e);
            throw $e;
        }
    }
}
