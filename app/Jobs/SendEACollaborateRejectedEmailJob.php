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

class SendEACollaborateRejectedEmailJob implements ShouldQueue
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
        $url = $this->birdUrl ?? (getAppStorageValueByKey(ApplicationStorageEnums::BIRD_EA_COLLABORATE_REJECTED_WORKFLOW_URL, false, true) ?? '');
        if (! $url) {
            LoggerService::warning('SendEACollaborateRejectedEmailJob: Bird URL not configured');

            return;
        }

        $managerEmails = User::whereHas('roles', fn ($q) => $q->where('name', RolesEnum::EAManager))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if (empty($managerEmails)) {
            LoggerService::info('SendEACollaborateRejectedEmailJob: No EA managers found, skipping', ['ref_id' => $this->quote->code ?? '']);

            return;
        }

        $this->quote->loadMissing(['advisor', 'expertAdvisor']);

        $payload = [
            'uuid' => $this->quote->uuid,
            'ref_id' => $this->quote->code,
            'quote_type' => $this->quoteType,
            'ea_model' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
            'customer_name' => trim($this->quote->first_name.' '.$this->quote->last_name),
            'manager_emails' => $managerEmails,
            'advisor_name' => $this->quote->advisor?->name ?? '',
            'advisor_email' => $this->quote->advisor?->email ?? '',
            'expert_advisor_name' => $this->quote->expertAdvisor?->name ?? '',
            'expert_advisor_email' => $this->quote->expertAdvisor?->email ?? '',
            'assigned_advisor_rejected_at' => $this->quote->ea_assigned_advisor_rejected_at?->toISOString(),
            'expert_advisor_rejected_at' => $this->quote->ea_expert_advisor_rejected_at?->toISOString(),
        ];

        app(BirdService::class)->triggerWebHookRequest($url, $payload);

        LoggerService::info('SendEACollaborateRejectedEmailJob: Sent via Bird', ['ref_id' => $this->quote->code]);
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('SendEACollaborateRejectedEmailJob: Failed', [
            'ref_id' => $this->quote->code ?? '',
            'error' => $exception->getMessage(),
        ]);
    }
}
