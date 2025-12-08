<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemovePrivateClientTagJob implements ShouldQueue
{
    use PrivateClient, Queueable;

    private array $uuids;

    /**
     * Create a new job instance.
     */
    public function __construct(array $uuids)
    {
        $this->uuids = $uuids;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info(self::class.': Private client tag removal has been initiated');

        $quotesQuery = PersonalQuote::whereNotNull('pc_qualified')
            ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
            ->where('quote_type_id', QuoteTypeId::Car)
            ->whereNotNull('policy_expiry_date')
            ->where('policy_expiry_date', '>', now());

        // Add uuids to query if provided
        if (! empty($this->uuids)) {
            $quotesQuery->whereIn('uuid', $this->uuids);
        }

        $quotesQuery->chunk(200, function ($quotes) {
            foreach ($quotes as $quote) {
                LoggerService::info(self::class.': Private client tag removal activity started', extra: [
                    'quote_uuid' => $quote->uuid,
                    'quote_type_id' => $quote->quote_type_id,
                ]);

                $this->removePcTagLead($quote->uuid, $quote->quote_type_id);

                LoggerService::info(self::class.': Private client tag removal activity completed', extra: [
                    'quote_uuid' => $quote->uuid,
                    'quote_type_id' => $quote->quote_type_id,
                ]);
            }
        });

        LoggerService::info(self::class.': Private client tag removal has been completed');
    }
}
