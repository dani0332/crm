<?php

declare(strict_types=1);

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\PersonalQuote;
use App\Services\BirdService;
use App\Services\DTTRevivalService;
use App\Services\HomeQuoteService;
use App\Services\HomeRevivalService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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

    public function handle(HomeRevivalService $homeRevivalService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_HOME_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            LoggerService::info(self::class.': DTT Home disabled in CMS', [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        LoggerService::startFeatureLogging(LoggerFeatureEnum::HOME_REVIVAL);
        LoggerService::info(self::class.' - handle - starting home revival job', [
            'personal_quote_id' => $this->personalQuoteId,
            'revival_source' => $this->revivalSource,
        ]);

        $this->lead = PersonalQuote::query()
            ->with('homeQuote')
            ->find($this->personalQuoteId);

        $lead = $this->lead;

        if ($lead === null || $lead->homeQuote === null) {
            LoggerService::info(self::class.' - lead or home quote not found', [
                'personal_quote_id' => $this->personalQuoteId,
                'revival_source' => $this->revivalSource,
            ]);

            return;
        }

        $lead->refresh();
        if ($lead->{$this->revivedFlagKey()}) {
            LoggerService::info(self::class.' - lead already revived', [
                'lead_uuid' => $lead->uuid,
                'revival_source' => $this->revivalSource,
            ]);

            return;
        }

        $homeQuote = $lead->homeQuote;
        $payload = $homeRevivalService->getRevivalPayload($lead, $homeQuote, $this->revivalSource);

        $existingRevivalQuote = PersonalQuote::select('uuid', 'id')
            ->where([
                'email' => $lead->email,
                'mobile_no' => $lead->mobile_no,
                'quote_type_id' => QuoteTypeId::Home,
                'source' => $this->revivalSource,
            ])
            ->first();

        $homeRevivalQuoteUUID = null;
        $existingDttRevival = null;

        if ($existingRevivalQuote) {
            $existingDttRevival = DttRevival::where([
                'quote_type_id' => QuoteTypes::HOME->id(),
                'uuid' => $existingRevivalQuote->uuid,
                'previous_quote_id' => $lead->id,
            ])->first();

            if ($existingDttRevival) {
                LoggerService::info(self::class.' - DTT revival already exists for quote, marking parent as revived and skipping', [
                    'lead_uuid' => $lead->uuid,
                    'existing_revival_quote_uuid' => $existingRevivalQuote->uuid,
                    'dtt_revival_id' => $existingDttRevival->id,
                ]);
                $lead->update([$this->revivedFlagKey() => true]);

                return;
            }

            LoggerService::info(self::class.' - '.$lead->uuid.' - existing revival quote belongs to a different lead, creating fresh revival');
        }

        if (! $existingDttRevival) {
            LoggerService::info(self::class.' - Creating Home Revival Lead', [
                'lead_uuid' => $lead->uuid,
                'revival_source' => $this->revivalSource,
                'payload' => $payload,
            ]);

            $capiResponse = Capi::request('/api/v2-save-home-quote', 'post', $payload);

            if (isset($capiResponse->errors) && empty($capiResponse->quoteUID)) {
                LoggerService::warning(self::class.' - Error creating Home Revival Lead from CAPI response', [
                    'lead_uuid' => $lead->uuid,
                    'url' => '/api/v2-save-home-quote',
                    'response' => $capiResponse,
                ]);

                return;
            }

            $homeRevivalQuoteUUID = $capiResponse->quoteUID;
            LoggerService::info(self::class.' - '.$lead->uuid.' - childLeadCreated - '.$homeRevivalQuoteUUID);
            try {
                app(HomeQuoteService::class)->getQuotePlans($homeRevivalQuoteUUID, ['getLatestRating' => true]);
            } catch (Exception $e) {
                LoggerService::warning(self::class.' - Error fetching home revival quote plans after creation', [
                    'quote_uuid' => $homeRevivalQuoteUUID,
                    'lead_uuid' => $lead->uuid,
                    'personal_quote_id' => $this->personalQuoteId,
                ], $e);
            }
        }

        $lead->refresh();

        if ($homeRevivalQuoteUUID && ! $existingDttRevival) {
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

            $homeRevivalQuote = PersonalQuote::where('uuid', $homeRevivalQuoteUUID)->first();

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

            $lead->update([$this->revivedFlagKey() => true]);

            LoggerService::info(self::class.' - New Home Revival Lead created successfully', [
                'quote_uuid' => $homeRevivalQuoteUUID,
                'parent_lead_uuid' => $lead->uuid,
                'revival_source' => $this->revivalSource,
            ]);

            if ($emailPayload !== null) {
                if (app(BirdService::class)->isFollowupExecuted($homeRevivalQuoteUUID, (int) QuoteTypes::HOME->id(), QuoteFlowType::HOME_REVIVAL_FOLLOWUP->value)) {
                    LoggerService::info(self::class.' - follow-up already executed', [
                        'dtt_revival_id' => $dttRevival->id,
                        'child_quote_uuid' => $homeRevivalQuoteUUID,
                        'revival_source' => $this->revivalSource,
                    ]);
                } else {
                    $emailPayload->workflowType = WorkflowTypeEnum::HOME_REVIVAL_FOLLOWUP;
                    HomeRevivalFollowUpEmailJob::dispatch($dttRevival->id, $emailPayload);

                    LoggerService::info(self::class.' - OCB done — dtt + parent updated + follow-up job queued', [
                        'dtt_revival_id' => $dttRevival->id,
                        'parent_lead_uuid' => $lead->uuid,
                        'child_quote_uuid' => $homeRevivalQuoteUUID,
                        'revival_source' => $this->revivalSource,
                    ]);
                }
            }
        }
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->personalQuoteId))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::warning(self::class.' - job failed', [
            'personal_quote_id' => $this->personalQuoteId,
        ], $exception);
    }

    private function revivedFlagKey(): string
    {
        return $this->revivalSource === LeadSourceEnum::REVIVAL_ANNUAL ? 'is_annual_revived' : 'is_revived';
    }
}
