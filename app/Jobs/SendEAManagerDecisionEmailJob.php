<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EaModelEnum;
use App\Enums\EnvEnum;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendEAManagerDecisionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Model $quote,
        private readonly string $quoteType,
        private readonly string $decision,
        private readonly ?int $templateId = null,
    ) {}

    public function handle(): void
    {
        $templateId = $this->templateId ?? (int) getAppStorageValueByKey(ApplicationStorageEnums::EA_MANAGER_DECISION_TEMPLATE_ID, false, true);

        if (! $templateId) {
            LoggerService::warning('SendEAManagerDecisionEmailJob: Brevo template not configured');

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

        $appEnv = config('constants.APP_ENV');
        $tag = $appEnv === EnvEnum::PRODUCTION ? 'ea-manager-decision' : "{$appEnv}-ea-manager-decision";

        $body = [
            'to' => array_map(fn ($email) => ['email' => $email], $recipientEmails),
            'templateId' => $templateId,
            'params' => [
                'refID' => $this->quote->code,
                'eaManager' => $this->quote->expertAdvisor?->name ?? '',
                'advisorName' => $this->quote->advisor?->name ?? '',
                'status' => $this->decision,
                'eaAdvisor' => $this->quote->expertAdvisor?->name ?? '',
                'lob' => $this->quoteType,
                'eaModel' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
                'leadSource' => $this->quote->source,
            ],
            'tags' => [$tag],
        ];

        LoggerService::info('SendEAManagerDecisionEmailJob: Sending to Brevo', [
            'ref_id' => $this->quote->code,
            'uuid' => $this->quote->uuid,
            'template_id' => $templateId,
            'to' => array_column($body['to'], 'email'),
            'params' => $body['params'],
            'tag' => $tag,
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'api-key' => config('constants.SENDINBLUE_KEY'),
            'Content-Type' => 'application/json',
        ])->post(config('constants.SIB_URL'), $body);

        LoggerService::info('SendEAManagerDecisionEmailJob: Brevo response', [
            'ref_id' => $this->quote->code,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->failed()) {
            LoggerService::warning('SendEAManagerDecisionEmailJob: Brevo returned non-2xx — will retry', [
                'ref_id' => $this->quote->code,
                'uuid' => $this->quote->uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            $response->throw();
        }

        LoggerService::info('SendEAManagerDecisionEmailJob: Sent via Brevo', [
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
