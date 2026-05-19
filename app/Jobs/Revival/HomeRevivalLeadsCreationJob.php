<?php

declare(strict_types=1);

namespace App\Jobs\Revival;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Services\BirdService;
use App\Services\DTTRevivalService;
use App\Services\HomeRevivalService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class HomeRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, GenericQueriesAllLobs, Queueable;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    protected $lead = null;

    public function __construct(
        private int $personalQuoteId,
        private string $revivalSource,
    ) {
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(HomeRevivalService $homeRevivalService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        LoggerService::startFeatureLogging(LoggerFeatureEnum::HOME_REVIVAL);
        LoggerService::info(self::class.' - handle - starting home revival job');

        $this->lead = PersonalQuote::query()
            ->with('homeQuote')
            ->where('is_revived', false)
            ->find($this->personalQuoteId);

        $lead = $this->lead;

        if ($lead === null || $lead->homeQuote === null) {
            LoggerService::info(self::class.' - lead or home quote not found', [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        $homeQuote = $lead->homeQuote;

        $payload = $homeRevivalService->getRevivalPayload($lead, $homeQuote, $this->revivalSource);

        LoggerService::info(self::class.' - Creating Home Revival Lead', [
            'lead_uuid' => $lead->uuid,
            'payload' => $payload,
        ]);
        $capiResponse = Capi::request('/api/v2-save-home-quote', 'post', $payload);

        if (isset($capiResponse->errors)) {
            LoggerService::warning(self::class.' - Error creating Home Revival Lead from CAPI response', [
                'lead_uuid' => $lead->uuid,
                'payload' => $payload,
                'url' => '/api/v2-save-home-quote',
                'response' => $capiResponse,
            ]);

            return;
        }

        $homeRevivalQuoteUUID = $capiResponse->quoteUID;

        $emailPayload = null;
        try {
            $emailPayload = $homeRevivalService->sendHomeRevivalEmail($homeRevivalQuoteUUID);
        } catch (Throwable $e) {
            LoggerService::warning(self::class.' - Error sending home revival email', [
                'quote_uuid' => $homeRevivalQuoteUUID,
                'lead_uuid' => $lead->uuid,
                'personal_quote_id' => $this->personalQuoteId,
            ], $e);
        }

        LoggerService::info(self::class.' - New Home Revival Lead created successfully', [
            'quote_uuid' => $homeRevivalQuoteUUID,
            'parent_lead_uuid' => $lead->uuid,
        ]);

        $dttRevival = null;
        $homeRevivalQuote = null;

        DB::transaction(function () use ($homeRevivalQuoteUUID, $lead, &$dttRevival, &$homeRevivalQuote): void {
            $homeRevivalQuote = PersonalQuote::where('uuid', $homeRevivalQuoteUUID)->first();
            LoggerService::info(self::class.' - Home revival child quote resolved', [
                'home_revival_quote_id' => $homeRevivalQuote?->id,
                'quote_uuid' => $homeRevivalQuoteUUID,
            ]);

            if ($homeRevivalQuote === null) {
                LoggerService::warning(self::class.' - Home revival quote not found in database', [
                    'quote_uuid' => $homeRevivalQuoteUUID,
                ]);

                return;
            }

            $dttRevival = app(DTTRevivalService::class)->create(
                $homeRevivalQuote->id,
                $homeRevivalQuoteUUID,
                $lead->id,
                QuoteTypes::HOME->id()
            );
            LoggerService::info(self::class.' - DTT Revival record created successfully', [
                'quote_uuid' => $homeRevivalQuoteUUID,
                'parent_lead_uuid' => $lead->uuid,
            ]);

            $lead->update(['is_revived' => true]);
            LoggerService::info(self::class.' - Home quote marked as revived', [
                'lead_uuid' => $lead->uuid,
            ]);
        });

        if ($emailPayload !== null && $dttRevival !== null && $homeRevivalQuote !== null) {
            if (app(BirdService::class)->isFollowupExecuted($homeRevivalQuoteUUID, (int) QuoteTypes::HOME->id(), QuoteFlowType::HOME_REVIVAL_FOLLOWUP->value)) {
                LoggerService::info(self::class.' - follow-up already executed', [
                    'dtt_revival_id' => $dttRevival->id,
                    'child_quote_uuid' => $homeRevivalQuoteUUID,
                ]);
            } else {
                $emailPayload->workflowType = WorkflowTypeEnum::HOME_REVIVAL_FOLLOWUP;
                HomeRevivalFollowUpEmailJob::dispatch($dttRevival->id, $emailPayload);

                LoggerService::info(self::class.' - OCB done — dtt + parent updated + follow-up job queued', [
                    'dtt_revival_id' => $dttRevival->id,
                    'parent_lead_uuid' => $lead->uuid,
                    'child_quote_uuid' => $homeRevivalQuoteUUID,
                ]);
            }
        }
    }

    public function middleware(): array
    {
        LoggerService::info(self::class.' - middleware - adding middleware');

        return [(new WithoutOverlapping($this->personalQuoteId))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::warning(self::class.' - job failed', [
            'personal_quote_id' => $this->personalQuoteId,
        ], $exception);
    }
}
