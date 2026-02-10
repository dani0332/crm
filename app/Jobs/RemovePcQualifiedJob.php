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

    public function __construct(array $uuids)
    {
        $this->uuids = $uuids;
        $this->onQueue('private-client');
    }

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

            $quotes = $quoteQuery->select('id', 'uuid', 'quote_type_id')->get();
            LoggerService::info(self::class.': PC qualified quotes found', extra: ['uuids' => $this->uuids, 'count' => $quotes->count()]);

            foreach ($quotes as $quote) {
                try {
                    $logData = [
                        'quote_id' => $quote->id,
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ];

                    LoggerService::info(self::class.': Removing PC qualified tagging started', extra: $logData);
                    $this->removePcQualified($quote->uuid, $quote->quote_type_id);
                    LoggerService::info(self::class.': Removing PC qualified tagging completed', extra: $logData);
                } catch (\Exception $e) {
                    LoggerService::error('Error removing PC qualified tag. Continuing with next quote.', extra: [
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ], exception: $e);
                    // Do not throw — continue with next quote
                }
            }

            LoggerService::info(self::class.': PC qualified removal exercise has been completed');
        } catch (\Exception $e) {
            LoggerService::error(self::class.': PC qualified removal exercise failed', exception: $e);
            throw $e;
        }
    }
}
