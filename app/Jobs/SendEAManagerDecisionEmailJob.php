<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EaModelEnum;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEAManagerDecisionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Model $quote,
        private readonly string $quoteType,
        private readonly string $decision,
        private readonly ?string $birdUrl = null,
    ) {}

    public function handle(): void
    {
        $url = $this->birdUrl ?? (getAppStorageValueByKey(ApplicationStorageEnums::BIRD_EA_MANAGER_DECISION_WORKFLOW_URL, false, true) ?? '');
        if (! $url) {
            LoggerService::warning('SendEAManagerDecisionEmailJob: Bird URL not configured');

            return;
        }

        $this->quote->loadMissing(['advisor', 'expertAdvisor']);

        $recipientEmails = collect([$this->quote->advisor?->email, $this->quote->expertAdvisor?->email])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($recipientEmails)) {
            LoggerService::info('SendEAManagerDecisionEmailJob: No recipients found, skipping', ['ref_id' => $this->quote->code ?? '']);

            return;
        }

        $payload = [
            'uuid' => $this->quote->uuid,
            'ref_id' => $this->quote->code,
            'quote_type' => $this->quoteType,
            'ea_model' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
            'decision' => $this->decision,
            'customer_name' => trim($this->quote->first_name.' '.$this->quote->last_name),
            'advisor_name' => $this->quote->advisor?->name ?? '',
            'advisor_email' => $this->quote->advisor?->email ?? '',
            'expert_advisor_name' => $this->quote->expertAdvisor?->name ?? '',
            'expert_advisor_email' => $this->quote->expertAdvisor?->email ?? '',
            'recipient_emails' => $recipientEmails,
        ];

        app(BirdService::class)->triggerWebHookRequest($url, $payload);

        LoggerService::info('SendEAManagerDecisionEmailJob: Sent via Bird', [
            'ref_id' => $this->quote->code,
            'decision' => $this->decision,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('SendEAManagerDecisionEmailJob: Failed', [
            'ref_id' => $this->quote->code ?? '',
            'decision' => $this->decision,
            'error' => $exception->getMessage(),
        ]);
    }
}
