<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EaModelEnum;
use App\Enums\EnvEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendEACollaborateRejectedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Model $quote,
        private readonly string $quoteType,
        private readonly ?int $templateId = null,
    ) {}

    public function handle(): void
    {
        $templateId = $this->templateId ?? (int) getAppStorageValueByKey(ApplicationStorageEnums::EA_COLLABORATE_REJECTED_TEMPLATE_ID, false, true);

        if (! $templateId) {
            LoggerService::warning('SendEACollaborateRejectedEmailJob: Brevo template not configured');

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

        $appEnv = config('constants.APP_ENV');
        $tag = $appEnv === EnvEnum::PRODUCTION ? 'ea-collaborate-rejected' : "{$appEnv}-ea-collaborate-rejected";

        $body = [
            'to' => array_map(fn ($email) => ['email' => $email], $managerEmails),
            'templateId' => $templateId,
            'params' => [
                'eaManager' => '',
                'eaAdvisor' => $this->quote->expertAdvisor?->name ?? '',
                'advisorName' => $this->quote->advisor?->name ?? '',
                'lob' => $this->quoteType,
                'eaModel' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
                'leadSource' => $this->quote->source,
            ],
            'tags' => [$tag],
        ];

        Http::withHeaders([
            'Accept' => 'application/json',
            'api-key' => config('constants.SENDINBLUE_KEY'),
            'Content-Type' => 'application/json',
        ])->post(config('constants.SIB_URL'), $body);

        LoggerService::info('SendEACollaborateRejectedEmailJob: Sent via Brevo', ['ref_id' => $this->quote->code]);
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('SendEACollaborateRejectedEmailJob: Failed', [
            'ref_id' => $this->quote->code ?? '',
            'error' => $exception->getMessage(),
        ]);
    }
}
