<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\PersonalQuote;
use App\Services\ApplicationStorageService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

class HomeRevivalFollowUpEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    private const LOG_FLOW = 'home_dtt_revival_followup';

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;

    /**
     * @param  object  $emailData  Same Bird payload as HomeRevivalLeadsCreationJob (workflowType will be overwritten to HOME_REVIVAL_FOLLOWUP).
     */
    public function __construct(
        public int $dttRevivalId,
        public ?object $emailData = null,
    ) {
        $this->onQueue('renewals');
    }

    public function handle(): void
    {
        if (! isset($this->emailData)) {
            LoggerService::warning(self::class.': emailData missing; skipping follow-up send', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HOME_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::warning(self::class.': DTT_HOME_ENABLED disabled in CMS (job was queued anyway)', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $dttRevival = DttRevival::find($this->dttRevivalId);

        if ($dttRevival === null) {
            LoggerService::warning(self::class.': dtt_revivals row missing', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $lead = PersonalQuote::where('uuid', $dttRevival->uuid)->first();

        $skipReason = null;
        if (! $lead) {
            $skipReason = 'no_child_personal_quote';
        } elseif (empty($dttRevival->created_at)) {
            $skipReason = 'dtt_revival_created_at_empty';
        } elseif (in_array($lead->quote_status_id, [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Fake])) {
            $skipReason = 'quote_status_duplicate_or_fake';
        } elseif (in_array($lead->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED])) {
            $skipReason = 'payment_authorised_or_captured';
        } elseif (in_array($lead->source, [LeadSourceEnum::REVIVAL_PAID])) {
            $skipReason = 'source_revival_paid';
        } elseif (! empty($lead->advisor_id)) {
            $skipReason = 'advisor_already_assigned';
        }

        if ($skipReason !== null) {
            LoggerService::warning(self::class.': follow-up not eligible', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $dttRevival->id,
                'child_quote_uuid' => $dttRevival->uuid,
                'skip_reason' => $skipReason,
                'advisor_id' => $lead?->advisor_id,
                'quote_status_id' => $lead?->quote_status_id,
                'payment_status_id' => $lead?->payment_status_id,
            ]);

            return;
        }

        try {
            $this->sendFollowUpEmail($this->emailData, $lead, $dttRevival);
        } catch (Throwable $exception) {
            LoggerService::warning(self::class.': exception in handle', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
                'child_quote_uuid' => $dttRevival->uuid,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function sendFollowUpEmail(object $emailData, PersonalQuote $lead, DttRevival $dttRevival): void
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_RENEWAL_OCB)->first();

        if (! $workflowUrl || empty($workflowUrl->value)) {
            LoggerService::warning(self::class.': HOME_RENEWAL_OCB URL missing in CMS', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $dttRevival->id,
                'child_quote_uuid' => $dttRevival->uuid,
            ]);

            throw new RuntimeException('HOME_RENEWAL_OCB URL missing in CMS');
        }

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

        if (in_array($response->status_code, [201, 200])) {
            DttRevival::where('id', $dttRevival->id)->increment('follow_up_email_count');

            app(BirdService::class)->createQuoteWorkFlowDetails(
                $lead,
                $response,
                QuoteFlowType::HOME_REVIVAL_FOLLOWUP->value,
                (int) QuoteTypes::HOME->id()
            );

            LoggerService::info(self::class.': follow-up email sent and status updated to FollowedUp', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $dttRevival->id,
                'child_quote_uuid' => $dttRevival->uuid,
            ]);
        } else {
            LoggerService::warning(self::class.': Bird follow-up call did not return success', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $dttRevival->id,
                'child_quote_uuid' => $dttRevival->uuid,
                'response_code' => $response->status_code,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $childQuoteUuid = DttRevival::query()->whereKey($this->dttRevivalId)->value('uuid');

        LoggerService::warning(self::class.': job failed after max tries', [
            'flow' => self::LOG_FLOW,
            'dtt_revival_id' => $this->dttRevivalId,
            'child_quote_uuid' => $childQuoteUuid,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
