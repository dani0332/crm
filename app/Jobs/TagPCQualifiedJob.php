<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TagPCQualifiedJob implements ShouldQueue
{
    use PrivateClient, Queueable;

    public $tries = 1;
    public array $uuids;

    public function __construct(array $uuids)
    {
        $this->uuids = $uuids;
        $this->onQueue('private-client');
    }

    public function handle(): void
    {
        LoggerService::info(self::class.': PC qualified tagging job has been initiated');
        try {
            $quotesQuery = PersonalQuote::where(function ($query) {
                $query->where('pc_qualified', false)
                    ->orWhereNull('pc_qualified');
            })
                ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->whereIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Life, QuoteTypeId::Yacht]);

            if (! empty($this->uuids)) {
                $quotesQuery->whereIn('uuid', $this->uuids);
            }

            $quotes = $quotesQuery->select('id', 'uuid', 'quote_type_id')->get();
            LoggerService::info(self::class.': PC qualified eligible quotes found', extra: ['uuids' => $this->uuids, 'count' => $quotes->count()]);

            foreach ($quotes as $quote) {
                try {
                    $logData = [
                        'quote_id' => $quote->id,
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ];

                    LoggerService::info(self::class.': PC qualified tagging activity started', extra: $logData);
                    $this->applyPcpTag($quote->uuid, $quote->quote_type_id);
                    LoggerService::info(self::class.': PC qualified tagging activity completed', extra: $logData);
                } catch (\Exception $e) {
                    LoggerService::error('Error PC qualified tagging. Continuing with next quote.', extra: [
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ], exception: $e);
                }
            }
        } catch (\Exception $e) {
            LoggerService::error(self::class.': PC qualified tagging job failed', exception: $e);
            throw $e;
        }
    }
}
