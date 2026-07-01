<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Throwable;

class CarRevivalFollowUpEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    private const LOG_FLOW = 'car_dtt_revival_followup';

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $dttRevival = null;

    /**
     * @param  object  $emailData  Same Bird payload as {@see CarRevivalLeadsCreationJob} (from {@see CarEmailService::buildDttRevivalBirdEmailPayload} when not dispatched from that job).
     */
    public function __construct(
        public int $dttRevivalId,
        public ?object $emailData = null,
    ) {
        $this->onQueue('renewals');
    }

    public function handle(): void
    {
        // returning for legacy leads which were queued before the enhancement
        if (! isset($this->emailData)) {
            LoggerService::warning(self::class.': emailData missing (legacy queued payload); skipping follow-up send', [
                'flow' => self::LOG_FLOW,
            ]);

            return;
        }

        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::warning(self::class.': DTT disabled in CMS (job was queued anyway)', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $this->dttRevival = DttRevival::find($this->dttRevivalId);

        if ($this->dttRevival === null) {
            LoggerService::warning(self::class.': dtt_revivals row missing', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
        $leadStatusArray = [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Fake];

        $created_at = $this->dttRevival->created_at;
        $lead = CarQuote::where('uuid', $this->dttRevival->uuid)->first();

        $skipReason = null;
        if (! $lead) {
            $skipReason = 'no_child_car_quote';
        } elseif (empty($created_at)) {
            $skipReason = 'dtt_revival_created_at_empty';
        } elseif (in_array($lead->quote_status_id, $leadStatusArray)) {
            $skipReason = 'quote_status_duplicate_or_fake';
        } elseif (in_array($lead->payment_status_id, $paymentStatusArray)) {
            $skipReason = 'payment_authorised_or_captured';
        } elseif (in_array($lead->source, $leadSourceArray)) {
            $skipReason = 'source_revival_paid';
        } elseif (! empty($lead->advisor_id)) {
            $skipReason = 'advisor_already_assigned';
        }

        if ($skipReason !== null) {
            LoggerService::warning(self::class.': follow-up not eligible', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevival->id,
                'child_quote_uuid' => $this->dttRevival->uuid,
                'skip_reason' => $skipReason,
                'advisor_id' => $lead?->advisor_id,
                'quote_status_id' => $lead?->quote_status_id,
                'payment_status_id' => $lead?->payment_status_id,
            ]);

            return;
        }

        try {
            $this->sendFollowUpEmail($this->emailData, $lead);
        } catch (Throwable $exception) {
            LoggerService::warning(self::class.': exception in handle', [
                'flow' => self::LOG_FLOW,
                'dtt_revival_id' => $this->dttRevivalId,
                'child_quote_uuid' => $this->dttRevival?->uuid,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function sendFollowUpEmail(object $emailData, CarQuote $lead): void
    {
        $emailData->uniqueId = (string) Str::ulid();
        app(WebEngageService::class)->sendEvent(app()->environment().'_'.WorkflowTypeEnum::MOTOR_REVIVAL_FOLLOWUP, (array) $emailData);

        DttRevival::where('id', $this->dttRevival->id)->increment('follow_up_email_count');

        app(WebEngageService::class)->createQuoteWorkFlowDetails($lead->uuid, QuoteFlowType::MOTOR_REVIVAL_FOLLOWUP->value, (int) QuoteTypes::CAR->id());
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
