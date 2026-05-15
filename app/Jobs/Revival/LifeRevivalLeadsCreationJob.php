<?php

declare(strict_types=1);

namespace App\Jobs\Revival;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Services\DTTRevivalService;
use App\Services\LifeRevivalService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class LifeRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, GenericQueriesAllLobs, Queueable;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    protected $lead = null;

    public function __construct(
        private int $personalQuoteId,
    ) {
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(LifeRevivalService $lifeRevivalService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        LoggerService::startFeatureLogging(LoggerFeatureEnum::LIFE_REVIVAL);
        LoggerService::info(self::class.' - handle - starting life revival job');

        $this->lead = PersonalQuote::query()
            ->with('lifeQuote')
            ->where('is_revived', false)
            ->find($this->personalQuoteId);

        $lead = $this->lead;

        if ($lead === null || $lead->lifeQuote === null) {
            LoggerService::info('Life revival job: lead or life quote not found', [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        $lifeQuote = $lead->lifeQuote;

        $payload = $lifeRevivalService->getRevivalPayload($lead, $lifeQuote);

        LoggerService::info('Creating Life Revival Lead', [
            'lead_uuid' => $lead->uuid,
            'payload' => $payload,
        ]);
        $capiResponse = Capi::request('/api/v2-save-life-quote', 'post', $payload);

        if (isset($capiResponse->errors)) {
            LoggerService::error('Error creating Life Revival Lead from CAPI response', [
                'lead_uuid' => $lead->uuid,
                'payload' => $payload,
                'url' => '/api/v2-save-life-quote',
                'response' => $capiResponse,
            ]);

            return;
        }

        try {
            $lifeRevivalService->sendLifeRevialEmail($capiResponse->quoteUID);
        } catch (Throwable $e) {
            LoggerService::warning('LifeRevivalLeadsCreationJob - Error sending DTT revival email', [
                'quote_uuid' => $capiResponse->quoteUID,
                'lead_uuid' => $lead->uuid,
                'personal_quote_id' => $this->personalQuoteId,
            ], $e);
        }

        $lifeRevivalQuoteUUID = $capiResponse->quoteUID;
        LoggerService::info('New Life Revival Lead created successfully', [
            'quote_uuid' => $lifeRevivalQuoteUUID,
            'parent_lead_uuid' => $lead->uuid,
        ]);

        DB::transaction(function () use ($lifeRevivalQuoteUUID, $lead) {
            // Get the life revival quote
            $lifeRevivalQuote = PersonalQuote::where('uuid', $lifeRevivalQuoteUUID)->first();
            LoggerService::info('Life revival child quote resolved', [
                'life_revival_quote_id' => $lifeRevivalQuote?->id,
                'quote_uuid' => $lifeRevivalQuoteUUID,
            ]);

            // Save DTT revival record
            $dttRevivalService = app(DTTRevivalService::class);
            $dttRevivalService->create($lifeRevivalQuote?->id ?? 0, $lifeRevivalQuoteUUID, $lead->id, QuoteTypes::LIFE->id());
            LoggerService::info('DTT Revival record created successfully', [
                'quote_uuid' => $lifeRevivalQuoteUUID,
                'parent_lead_uuid' => $lead->uuid,
            ]);

            // Mark life quote as revived
            $lead->update(['is_revived' => true]);
            LoggerService::info('Life quote marked as revived', [
                'lead_uuid' => $lead->uuid,
            ]);
        });
    }

    public function middleware(): array
    {
        LoggerService::info(self::class.' - middleware - adding middleware');

        return [(new WithoutOverlapping($this->personalQuoteId))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error('LifeRevivalLeadsCreationJob failed', [
            'personal_quote_id' => $this->personalQuoteId,
        ], $exception);
    }
}
