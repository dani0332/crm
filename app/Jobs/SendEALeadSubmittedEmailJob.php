<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EaModelEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEALeadSubmittedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Model $quote,
        private readonly string $quoteType,
        private readonly ?string $birdUrl = null,
    ) {}

    public function handle(): void
    {
        $url = $this->birdUrl ?? (getAppStorageValueByKey(ApplicationStorageEnums::BIRD_EA_LEAD_SUBMITTED_WORKFLOW_URL, false, true) ?? '');
        if (! $url) {
            LoggerService::warning('SendEALeadSubmittedEmailJob: Bird URL not configured');

            return;
        }

        $this->quote->loadMissing(['advisor', 'leadGenerator']);

        if (! $this->quote->advisor?->email) {
            LoggerService::info('SendEALeadSubmittedEmailJob: No advisor email, skipping', ['ref_id' => $this->quote->code ?? '']);

            return;
        }

        $managerEmails = User::whereHas('roles', fn ($q) => $q->where('name', RolesEnum::EAManager))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        $payload = [
            'uuid' => $this->quote->uuid,
            'ref_id' => $this->quote->code,
            'quote_type' => $this->quoteType,
            'ea_model' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
            'customer_name' => trim($this->quote->first_name.' '.$this->quote->last_name),
            'advisor_name' => $this->quote->advisor->name,
            'advisor_email' => $this->quote->advisor->email,
            'lead_generator_name' => $this->quote->leadGenerator?->name ?? '',
            'lead_generator_email' => $this->quote->leadGenerator?->email ?? '',
            'manager_emails' => $managerEmails,
        ];

        app(BirdService::class)->triggerWebHookRequest($url, $payload);

        LoggerService::info('SendEALeadSubmittedEmailJob: Sent via Bird', ['ref_id' => $this->quote->code]);
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('SendEALeadSubmittedEmailJob: Failed', [
            'ref_id' => $this->quote->code ?? '',
            'error' => $exception->getMessage(),
        ]);
    }
}
